<?php echo '<%'; ?> f = form_for('<?php echo $singular_name; ?>') %>

<?php  echo '<%'?>= error_messages_for '<?php  echo $singular_name?>' %>

<?php foreach($attributes as $attribute) : ?>
  <div class="field">
    <?php echo '<?='; ?> $f->label('<?php echo $attribute['name']; ?>'); ?><br />
    <?php echo '<?='; ?> $f-><?php echo $attribute['type']; ?>('<?php echo $attribute['name']; ?>'); ?>
  </div>
<?php endforeach; ?>
  <div class="actions">
    <?php echo '<?='; ?> $f->submit(); ?>
  </div>
</form>
