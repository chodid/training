#!/bin/bash
# next.sh <fertige Einheit> [n] – entfernt aus running.txt, holt n neue aus der Warteschlange
S=/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad
sed -i "/^$1\$/d" $S/u2/running.txt
N=${2:-1}; NEW=$(head -n $N $S/u2/queue.txt); sed -i "1,${N}d" $S/u2/queue.txt
[ -n "$NEW" ] && echo "$NEW" >> $S/u2/running.txt
echo "$NEW"; echo "laufend: $(wc -l < $S/u2/running.txt), Warteschlange: $(wc -l < $S/u2/queue.txt)"
