<?php
/** Ist-Felder einer Übung (S3 und S9, gleiche Feldnamen ist[i][…]). @var int $i @var array $x Übung @var array $ist Vorbelegung */
?>
          <div class="ist">
            <div class="field"><label for="ist-<?= $i ?>-sets">Sätze</label><input class="input mono" id="ist-<?= $i ?>-sets" name="ist[<?= $i ?>][sets]" value="<?= $this->e($ist['sets'] ?? '') ?>" inputmode="numeric"></div>
            <div class="field"><label for="ist-<?= $i ?>-reps">Wdh.</label><input class="input mono" id="ist-<?= $i ?>-reps" name="ist[<?= $i ?>][reps]" value="<?= $this->e($ist['reps'] ?? '') ?>"></div>
            <div class="field"><label for="ist-<?= $i ?>-load">Last</label><input class="input mono" id="ist-<?= $i ?>-load" name="ist[<?= $i ?>][load]" value="<?= $this->e($ist['load'] ?? '') ?>"></div>
          </div>
