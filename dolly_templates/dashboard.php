<div class="topbar">
    <h1 class="main-title"><?=l10n()->dashboard?></h1>
</div>
<div class="forms inner">
    <div class="tabs">
        <div class="tabs_selectors">
            <ul class="list">
                <li id="tab_2"><?=l10n()->orders?></li>
            </ul>
        </div>
        <div class="tabs_content">
            <div class="tab tab_2">
                <div class="orders">
                    <div class="orders_list">
                        <div class="thead">
                            <div class="col col-xs-5 pd0">
                                <span class="label"><?=l10n()->text?></span>
                            </div>
                            <div class="col-xs-2 pd0 col">
                                <span class="label"><?=l10n()->date?></span>
                            </div>
                            <div class="col-xs-5 pd0 col">
                                <span class="label"><?=l10n()->action?></span>
                            </div>
                        </div>
                        <div class="tbody">
<?php if ($orders) {
    foreach ($orders as $key => $order) {
        if (@trim($order['content'])):
            ?>
            <div class="item">
                <div class="col-xs-5 pd0">
                    <label for="checkbox[1]">
                        <?php echo str_replace("\n", '<br>', $order['content']); ?>
                    </label>
                </div>
                <div class="col col-xs-2 pd0">
                    <?php echo $order['date']; ?>
                </div>
                <div class="col col-xs-5 pd0">
                    <a href="/admin.php?action=del_order&id=<?php echo $key; ?>"><?=l10n()->delete?></a>
                </div>
            </div>
            <?php
        endif;
    }
}
 ?>                     </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>