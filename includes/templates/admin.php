<div class="wrap">
  <h2 class="gc_title"><img src="<?php echo $this->includes_url ?>/images/gc-thumb.svg" width="30px" />GotChosen Integration</h2>
  <div>
    <form action="" method="post">
      <?php wp_nonce_field('got chosen save options'); ?>
      <h3>Feed Key</h3>
      <div class="gc_opts_group">
        <div class="gc_option">
          <label for="feedkey">Enter Feed Key provided to your publisher account: </label>
          <input type="text" value="<?php echo $this->options['feedkey'] ?>" name="feedkey" id="feedkey" />
        </div>
      </div>
      <h3>Webcurtain Settings</h3>
      <div class="gc_opts_group">
        <div class="gc_option">
          <label for="webcurtain">Enable the webcurtain: </label>
          <input type="checkbox" name="webcurtain" id="webcurtain" <?php echo($this -> options['webcurtain']) ? 'checked="checked"' : ''; ?>
          />
        </div>
        <div class="gc_option">
          <label for="webcurtain_compat">Enable compatability mode: </label>
          <input type="checkbox" name="webcurtain_compat" id="webcurtain_compat" <?php echo($this -> options['webcurtain_compat']) ? 'checked="checked"' : ''; ?>
          />
          <p class="description">
            Use compatability mode if the webcurtain is not displaying properly on your site.
          </p>
        </div>
      </div>
      <h3>Social Exchange Options</h3>
      <div class="gc_opts_group">
        <div class="gc_option">
          <label for="pub_minifeed_default">Default setting for publishing posts to the Social Exchange: </label>
          <input type="checkbox" name="pub_minifeed_default" id="pub_minifeed_default" <?php echo($this -> options['pub_minifeed_default']) ? 'checked="checked"' : ''; ?>
          />
        </div>
        <div class="gc_option">
          <label for="shareable">Make Social Exchange posts shareable: </label>
          <input type="checkbox" name="shareable" id="shareable" <?php echo($this -> options['shareable']) ? 'checked="checked"' : ''; ?>
          />
        </div>
        <div class="gc_option">
          <label for="commentable">Make Social Exchange posts commentable: </label>
          <input type="checkbox" name="commentable" id="commentable" <?php echo($this -> options['commentable']) ? 'checked="checked"' : ''; ?>
          />
        </div>
      </div>
      <input type="submit" class="button button-primary" value="Update Options">
    </form>
  </div>
</div>