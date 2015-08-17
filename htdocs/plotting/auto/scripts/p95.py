import psycopg2.extras
import numpy as np
from pyiem import network
import datetime
import pandas as pd

PDICT = {'none': 'Show all values',
         'hide': 'Show "strong" events'}


def get_description():
    """ Return a dict describing how to call this plotter """
    d = dict()
    d['data'] = True
    d['description'] = """This chart displays the combination of average
    temperature and total precipitation for one or more months of your choice.
    The dots are colorized based on the Southern Oscillation Index (SOI) value
    for a month of your choice.  Many times, users want to compare the SOI
    value with monthly totals for a period a few months after the validity of
    the SOI value.  The thought is that there is some lag time for the impacts
    of a given SOI to be felt in the midwestern US.
    """
    d['arguments'] = [
        dict(type='station', name='station', default='IA0000',
             label='Select Station'),
        dict(type='month', name='month', default=9,
             label='Start Month:'),
        dict(type='text', name='months', default=2,
             label='Number of Months to Average:'),
        dict(type='text', name='lag', default=-3,
             label='Number of Months to Lag for SOI Value:'),
        dict(type="select", name='h', default='none', options=PDICT,
             label='Hide/Show week SOI events -0.5 to 0.5'),
    ]
    return d


def title(wanted):
    """ Make a title """
    t1 = datetime.date(2000, wanted[0], 1)
    t2 = datetime.date(2000, wanted[-1], 1)
    return "Avg Precip + Temp for %s thru %s," % (t1.strftime("%B"),
                                                  t2.strftime("%B"))


def plotter(fdict):
    """ Go """
    import matplotlib
    matplotlib.use('agg')
    import matplotlib.pyplot as plt
    import matplotlib.colors as mpcolors
    COOP = psycopg2.connect(database='coop', host='iemdb', user='nobody')
    ccursor = COOP.cursor(cursor_factory=psycopg2.extras.DictCursor)

    station = fdict.get('station', 'IA0000')
    lagmonths = int(fdict.get('lag', -3))
    months = int(fdict.get('months', 2))
    month = int(fdict.get('month', 9))
    h = fdict.get('h', 'none')

    wantmonth = month + lagmonths
    yearoffset = 0
    if month + lagmonths < 1:
        wantmonth = 12 - (month + lagmonths)
        yearoffset = 1

    wanted = []
    deltas = []
    for m in range(month, month+months):
        if m < 13:
            wanted.append(m)
            deltas.append(0)
        else:
            wanted.append(m-12)
            deltas.append(-1)

    table = "alldata_%s" % (station[:2],)
    nt = network.Table("%sCLIMATE" % (station[:2],))

    elnino = {}
    ccursor.execute("""SELECT monthdate, soi_3m, anom_34 from elnino""")
    for row in ccursor:
        if row[0].month != wantmonth:
            continue
        elnino[row[0].year + yearoffset] = dict(soi_3m=row[1], anom_34=row[2])

    ccursor.execute("""
        SELECT year, month, sum(precip), avg((high+low)/2.)
        from """ + table + """
        where station = %s GROUP by year, month
    """, (station, ))
    yearly = {}
    for row in ccursor:
        (_year, _month, _precip, _temp) = row
        if _month not in wanted:
            continue
        effectiveyear = _year + deltas[wanted.index(_month)]
        nino = elnino.get(effectiveyear, {}).get('soi_3m', None)
        if nino is None:
            continue
        data = yearly.setdefault(effectiveyear, dict(precip=0, temp=[],
                                                     nino=nino))
        data['precip'] += _precip
        data['temp'].append(float(_temp))

    fig, ax = plt.subplots(1, 1)
    msg = ("[%s] %s\n%s\n%s SOI (3 month average)"
           ) % (station, nt.sts[station]['name'], title(wanted),
                datetime.date(2000, wantmonth, 1).strftime("%B"))
    ax.set_title(msg)

    cmap = plt.get_cmap("RdYlGn")
    zdata = np.arange(-2.0, 2.1, 0.5)
    norm = mpcolors.BoundaryNorm(zdata, cmap.N)
    rows = []
    xs = []
    ys = []
    for year in yearly:
        x = yearly[year]['precip']
        y = np.average(yearly[year]['temp'])
        xs.append(x)
        ys.append(y)
        val = yearly[year]['nino']
        c = cmap(norm([val])[0])
        if h == 'hide' and val > -0.5 and val < 0.5:
            ax.scatter(x, y, facecolor='#EEEEEE', edgecolor='#EEEEEE', s=30,
                       zorder=2, marker='s')
        else:
            ax.scatter(x, y, facecolor=c, edgecolor='k', s=60, zorder=3,
                       marker='o')
        rows.append(dict(year=year, precip=x, tmpf=y, soi3m=val))

    ax.axhline(np.average(ys), lw=2, color='k', linestyle='-.', zorder=2)
    ax.axvline(np.average(xs), lw=2, color='k', linestyle='-.', zorder=2)

    sm = plt.cm.ScalarMappable(norm, cmap)
    sm.set_array(zdata)
    cb = fig.colorbar(sm, extend='both')
    cb.set_label("<-- El Nino :: SOI :: La Nina -->")

    ax.grid(True)
    ax.set_xlim(left=-0.01)
    ax.set_xlabel("Total Precipitation [inch], Avg: %.2f" % (np.average(xs),))
    ax.set_ylabel("Average Temperature $^\circ$F, Avg: %.1f" % (np.average(ys),
                                                                ))
    df = pd.DataFrame(rows)
    plt.tight_layout()

    return fig, df
