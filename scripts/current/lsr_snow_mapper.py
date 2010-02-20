# Generate current plot of air temperature

import sys, os, numpy
sys.path.append("../lib/")
import iemplot

import mx.DateTime, random
now = mx.DateTime.now()

from pyIEM import iemdb
i = iemdb.iemdb()
postgis = i['postgis']

vals = []
valmask = []
lats = []
lons = []
rs = postgis.query("""SELECT state, 
      max(magnitude) as val, x(geom) as lon, y(geom) as lat
      from lsrs_2010 WHERE type in ('S') and magnitude > 0 and 
      valid > now() - '12 hours'::interval
      GROUP by state, lon, lat""").dictresult()
for i in range(len(rs)):
  vals.append( rs[i]['val'] )
  lats.append( rs[i]['lat'] + (random.random() * 0.001) )
  lons.append( rs[i]['lon'] )
  valmask.append( rs[i]['state'] in ['IA',] )
  #valmask.append( False )

# Now, we need to add in zeros, lets say we are looking at a .25 degree box
buffer = 1.0
for lat in numpy.arange(iemplot.IA_SOUTH, iemplot.IA_NORTH, buffer):
  for lon in numpy.arange(iemplot.IA_WEST, iemplot.IA_EAST, buffer):
    found = False
    for j in range(len(lats)):
      if (lats[j] > (lat-(buffer/2.)) and lats[j] < (lat+(buffer/2.)) and
         lons[j] > (lon-(buffer/2.)) and lons[j] < (lon+(buffer/2.)) ):
        found = True
    if not found:
      lats.append( lat )
      lons.append( lon )
      valmask.append( False )
      vals.append( 0 )

cfg = {
 'wkColorMap': 'BlAqGrYeOrRe',
 'nglSpreadColorStart': 2,
 'nglSpreadColorEnd'  : -1,
 '_valuemask'         : valmask,
 '_title'             : "Local Storm Report Snowfall Total Analysis",
 '_valid'             : "Reports past 12 hours: "+ now.strftime("%d %b %Y %I:%M %p"),
 '_showvalues'        : True,
 '_format'            : '%.1f',
 '_MaskZero'          : True,
 'lbTitleString'      : "[in]",
 'pmLabelBarHeightF'  : 0.6,
 'pmLabelBarWidthF'   : 0.1,
 'lbLabelFontHeightF' : 0.025
}
# Generates tmp.ps
tmpfp = iemplot.simple_contour(lons, lats, vals, cfg)

os.system("convert -depth 8 -rotate -90 -trim -border 5 -bordercolor '#fff' -resize 900x700 -density 120 +repage %s.ps %s.png" % (tmpfp, tmpfp) )
os.system("/home/ldm/bin/pqinsert -p 'plot c 000000000000 lsr_snowfall.png bogus png' %s.png" % (tmpfp,) )
if os.environ['USER'] == 'akrherz':
  os.system("xv %s.png" % (tmpfp,))
os.system("convert -depth 8 -rotate -90 -trim -border 5 -bordercolor '#fff' -resize 320x210 -density 120 +repage %s.ps %s.png" % (tmpfp, tmpfp) )
os.system("/home/ldm/bin/pqinsert -p 'plot c 000000000000 lsr_snowfall_thumb.png bogus png' %s.png" % (tmpfp,))
os.remove("%s.png" % (tmpfp,) )
os.remove("%s.ps" % (tmpfp,) )
