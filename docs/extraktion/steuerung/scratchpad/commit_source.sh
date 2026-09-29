#!/bin/bash
# Verwendung: commit_source.sh <L-ID> [<L-ID> ...]  – prüft, aktualisiert README, committet je Quelle
S=/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad
cd /home/user/training || exit 1
for ID in "$@"; do
  RES=$(python3 $S/check.py $ID)
  N=$(echo "$RES" | wc -l)
  if echo "$RES" | grep -q '"fehler": \["fehlt"\]'; then echo "$ID: noch nicht vollständig"; echo "$RES" | grep '"fehlt"' | cut -c1-150; continue; fi
  if echo "$RES" | grep -qv '"fehler": \[\]'; then echo "$ID: Formfehler"; echo "$RES" | grep -v '"fehler": \[\]' | cut -c1-300; continue; fi
  python3 $S/status_gen.py >/dev/null && python3 $S/readme.py >/dev/null
  BLOCK=$(python3 -c "import json;u=[x for x in json.load(open('$S/u2/units.json')) if x['id']=='$ID'][0];print(u['block'])")
  KAP=$(python3 -c "import json;u=[x for x in json.load(open('$S/u2/units.json')) if x['id']=='$ID'];print('Artikel' if u[0]['nr']=='00' and len(u)==1 and u[0]['titel'].startswith('Artikel') else f'{len(u)} Kapitel')")
  git add -f docs/extraktion/$BLOCK/$ID && git add docs/extraktion/README.md
  git commit -q -m "docs(extraktion): $ID extrahiert ($KAP)

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01GFFAgWyQw9M3MopmTCbEbx" && echo "$ID: committet ($KAP)"
  echo "$RES" | python3 -c "
import sys,json
for l in sys.stdin:
    r=json.loads(l); print('  ',r['path'].split('/')[-1],'A',r['aussagen'],'U',r['unsicher'],'O',r['offene'],'unklar',r['unklar'],'NV' if r['nicht_verwertbar'] else '',r['seiten'],r['lesemethode'])"
done
git push -q origin claude/ecstatic-johnson-g3mxel 2>&1 | tail -1
