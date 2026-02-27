<table id="example2" class="table table-striped table-hover align-middle TableData">
    <thead class="table-light">
    <tr>
        <th>#</th>
        <th>Code</th>
        <th>Bold</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($lab_test_option ?? []); ++$i) { ?>
        <tr>
            <td><?= esc($lab_test_option[$i]->sort_id ?? '') ?></td>
            <td><?= esc($lab_test_option[$i]->option_value ?? '') ?></td>
            <td><?= esc($lab_test_option[$i]->option_bold_str ?? '') ?></td>
            <td>
                <div class="btn-group-horizontal">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="remove_option('<?= esc($lab_test_option[$i]->id ?? 0) ?>','<?= esc($mstTestKey ?? 0) ?>')">Remove</button>
                    <?php
                    $option_current = $lab_test_option[$i]->id ?? 0;
                    $sort_current = $lab_test_option[$i]->sort_id ?? 0;
                    if ($i + 1 < count($lab_test_option ?? [])) {
                        $option_next = $lab_test_option[$i + 1]->id ?? 0;
                        $sort_next = $lab_test_option[$i + 1]->sort_id ?? 0;
                        echo '<button type="button" class="btn btn-outline-primary btn-sm" onclick="sortchange(' . (int) ($mstTestKey ?? 0) . ',' . (int) $option_current . ',' . (int) $sort_current . ',' . (int) $option_next . ',' . (int) $sort_next . ')">Down</button>';
                    }
                    if ($i > 0) {
                        $option_prev = $lab_test_option[$i - 1]->id ?? 0;
                        $sort_prev = $lab_test_option[$i - 1]->sort_id ?? 0;
                        echo '<button type="button" class="btn btn-outline-primary btn-sm" onclick="sortchange(' . (int) ($mstTestKey ?? 0) . ',' . (int) $option_current . ',' . (int) $sort_current . ',' . (int) $option_prev . ',' . (int) $sort_prev . ')">Up</button>';
                    }
                    ?>
                </div>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
