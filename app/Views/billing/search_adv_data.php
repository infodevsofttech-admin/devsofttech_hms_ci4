<?php if (!empty($search_result)) : ?>
    <ul class="list-group">
        <?php foreach ($search_result as $row) : ?>
            <li class="list-group-item">
                <?= esc($row->Sresult ?? '') ?>
            </li>
        <?php endforeach ?>
    </ul>
<?php else : ?>
    <div class="text-muted">No matching records.</div>
<?php endif ?>
