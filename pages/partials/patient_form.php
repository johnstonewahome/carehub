<form class="stack-form" method="post">
    <?= csrf_field() ?>
    <div class="field-row">
        <label>First name
            <input name="first_name" required value="<?= e($data['first_name']) ?>">
        </label>
        <label>Last name
            <input name="last_name" required value="<?= e($data['last_name']) ?>">
        </label>
    </div>
    <div class="field-row">
        <label>Sex
            <select name="sex">
                <?php foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= $data['sex'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date of birth
            <input type="date" name="dob" value="<?= e($data['dob']) ?>">
        </label>
    </div>
    <label>Phone
        <input name="phone" value="<?= e($data['phone']) ?>">
    </label>
    <label>Address
        <input name="address" value="<?= e($data['address']) ?>">
    </label>
    <label><?= e(pmshx_label()) ?>
        <textarea name="allergies" rows="3" placeholder="Hypertension, appendectomy in 2019 — leave blank if none"><?= e($data['allergies']) ?></textarea>
    </label>
    <label>Medical history
        <textarea name="medical_history" rows="3"><?= e($data['medical_history']) ?></textarea>
    </label>
    <button class="btn" type="submit"><?= e($submitLabel ?? 'Save chart') ?></button>
</form>
