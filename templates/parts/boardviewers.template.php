                    <font size="1"><b>������ � �������</b>
                      <?php $this->menu_begin() ?>
                      <?php foreach($this->boardviewers[0] as $boardviewer): ?>
                        <?php $this->menusep(',') ?>
                        <a href="<?= SITE_ROOT ?>/profile/<?= $this->esc($boardviewer['identity']) ?>/"><font class="<?= $this->rse($boardviewer['membergroup']) ?>"><?= $this->esc($boardviewer['realname']) ?></a></font>
                      <?php endforeach; ?>
                      <?php if (count($this->boardviewers[0]) > 0 and $this->boardviewers[1] > 0): ?>
                        � 
                      <?php endif; ?>
                      <?php if ($this->boardviewers[1] > 0): /* guests */?>
                        <?php if ($this->boardviewers[1] % 10  == 1 and $this->boardviewers[1] != 11): ?>
                          <?= $this->boardviewers[1] ?> �����
                        <?php elseif ($this->boardviewers[1] % 10  > 1 and $this->boardviewers[1] % 10  < 5 and !($this->boardviewers[1] > 11 and $this->boardviewers[1] < 15)): ?>
                          <?= $this->boardviewers[1] ?> �����
                        <?php else: ?>
                          <?= $this->boardviewers[1] ?> ������
                        <?php endif; ?>
                      <?php endif; /* guests */?>
                    </font>
