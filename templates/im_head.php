<?php /* this included from base template */ ?>

<?php if ($this->user->name != 'Guest'): ?>
  <span id="instantMessages">
    <?php if ($this->imcount[1] == 0): ?>
        <?= $this->locale->txt[152] ?> <a href="<?= SITE_ROOT ?>/im/"><?= $this->imcount[0] ?> <?= $this->locale->txt[153] ?></a>
    <?php elseif ($this->imcount[0] == '1'): ?>
        <?= $this->locale->txt(152) ?> <a href="<?= SITE_ROOT ?>/im/"><?= $this->imcount[0] ?> <?= $this->locale->txt(471) ?></a> <?= $this->locale->newmessages2 ?><?= $this->imcount[1] ?>)
    <?php else: ?>
        <?= $this->locale->txt(152) ?> <a href="<?= SITE_ROOT ?>/im/"><?= $this->imcount[0] ?> <?= $this->locale->txt(153) ?></a> <?= $this->locale->newmessages2 ?><?= $this->imcount[1] ?>)
    <?php endif; ?>
    
    <?php if ($this->imsound): ?>
        <script type="text/javascript">Forum.Utils.playMP3OnBackground('<?= $this->imsound ?>');</script>
    <?php endif; ?>
  </span>

  <?php if ($this->numComments == 1): ?>
      � <?= $this->numComments ?> ����� �����������
  <?php elseif ($this->numComments > 1 and $this->numComments < 5): ?>
      � <?= $this->numComments ?> ����� �����������
  <?php elseif ($this->numComments >= 5): ?>
      � <?= $this->numComments?> ����� ������������
  <?php endif; ?>
  
  <?php if ($this->numComments > 0): ?>
      <?php if ($this->numUnreadComments > 0): ?>
          <?php if ($this->numOtherComments > 0): ?>
              :<br><a href="<?= SITE_ROOT ?>/people/<?= $this->user->name ?>/recentcomments/"><?= $this->numUnreadComments ?> � �����</a>
          <?php else: ?>
              <?php if ($this->numComments > 1): ?>
                  <br><a href="<?= SITE_ROOT ?>/people/<?= $this->user->name ?>/recentcomments/">� �����</a>
              <?php else: ?>
                  <br><a href="<?= SITE_ROOT ?>/people/<?= $this->user->name ?>/recentcomments/">� ������</a>
              <?php endif; ?>
          <?php endif; /* numOtherComments */ ?>
      <?php endif; /* numUnreadComments */ ?>
          
      <?php if ($this->numOtherComments > 0): ?>
          <?php if ($this->numUnreadComments > 0): ?>
              , <a href="<?= SITE_ROOT ?>/people/<?= $this->user->name ?>subscribedmessagecomments/"><?= $this->numOtherComments ?> � �����</a>
          <?php else: ?>
              <?php if ($this->numComments > 1): ?>
                  <br><a href="<?= SITE_ROOT ?>/people/<?= $this->user->name ?>/subscribedmessagecomments/">� �����</a>
              <?php else: ?>
                  <br><a href="<?= SITE_ROOT ?>/people/<?= $this->user->name ?>/subscribedmessagecomments/">� ������</a>
              <?php endif; ?>
          <?php endif; /* numUnreadComments */ ?>
      <?php endif; /* numOtherComments */ ?>
          
      <?php if ($this->numComments > 1): ?>
          ����������
      <?php else: ?>
          ���������
      <?php endif; ?>
      
  <?php endif; /* numComments */ ?>
        


  <?php if ($this->conf->maintenance): ?>
    <br><b><?= $this->locale->txt(616) ?></b>
  <?php endif; ?>


<?php endif; /* user->name not guest */?>
