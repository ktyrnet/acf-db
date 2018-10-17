<?php $key = $field['columnname4editor'];
$keysave = $field['columnnames4save'];
foreach ($posts as $_post):?>
    <li><div class="acf-rel-item" data-id="<?php echo $_post->$keysave;?>"><div class="acf-rel-item-head"><?php echo $this->esc_html($keysave);?>:<?php echo $this->esc_html($_post->$keysave);?></div><div class="acf-rel-item-title"><?php echo $this->esc_html($_post->$key);?></div><a href="#" class="acf-icon -minus remove-item small dark" data-name="remove_item"></a></div></li>
<?php endforeach;?>