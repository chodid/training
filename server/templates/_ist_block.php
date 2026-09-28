<?php
/** Ist-Felder eines Kletterblocks (S3 und S9); Sätze nur, wenn geplant. @var int $i @var array $b Block @var array $ist Vorbelegung @var array $data */
?>
          <div class="ist">
            <div class="field"><label for="ist-<?= $i ?>-dur">Dauer (min)</label><input class="input mono" id="ist-<?= $i ?>-dur" name="ist[<?= $i ?>][duration_min]" value="<?= $this->e($ist['duration_min'] ?? '') ?>" inputmode="numeric"></div>
<?php if (isset($b['sets'])): ?>
            <div class="field"><label for="ist-<?= $i ?>-sets">Sätze</label><input class="input mono" id="ist-<?= $i ?>-sets" name="ist[<?= $i ?>][sets]" value="<?= $this->e($ist['sets'] ?? '') ?>" inputmode="numeric"></div>
            <div class="field"><label for="ist-<?= $i ?>-notes">Notiz</label><input class="input" id="ist-<?= $i ?>-notes" name="ist[<?= $i ?>][notes]" value="<?= $this->e(($data['ist'][$i]['notes'] ?? null) !== ($b['notes'] ?? null) ? ($ist['notes'] ?? '') : '') ?>" placeholder="optional"></div>
<?php else: ?>
            <div class="field span-2"><label for="ist-<?= $i ?>-notes">Notiz</label><input class="input" id="ist-<?= $i ?>-notes" name="ist[<?= $i ?>][notes]" value="<?= $this->e(($data['ist'][$i]['notes'] ?? null) !== ($b['notes'] ?? null) ? ($ist['notes'] ?? '') : '') ?>" placeholder="optional"></div>
<?php endif ?>
          </div>
