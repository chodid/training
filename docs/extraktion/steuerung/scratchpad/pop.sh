#!/bin/bash
# gibt die nächsten N Einheiten aus und entfernt sie aus der Warteschlange
Q=/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad/u2/queue.txt
head -n ${1:-1} $Q; sed -i "1,${1:-1}d" $Q; echo "rest: $(wc -l < $Q)"
