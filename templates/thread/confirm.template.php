      <p>������� ������ "�����������", � �������, ��� ������ ��������� ������� �� ���� ��������
       ������� ������ � ��� �� �������� ������ ��� ������. �� ��������� ������ ������
       � ���� ������ ���������������.</p>
      <p><b>� ���������� � ����������� ������� ������ � ���, ��� � ������ ������������ ����� � ��� �� �����</b> ��� ������� �� ���� ������. �������� � ���, ���
      � ��������� ������ ��� ����� ���� ���������� ����������� ������ �� ������.</p>

      <form action="." name="cform" id="cform" method="POST">
        <input type="hidden" name="cconfirm" value="<?= $this->cValue ?>">
        <input type="hidden" name="input<?= $this->cValue%7 ?>" value="YES">
        <input type="hidden" name="sc" value="<?= $this->app->session->id ?>">
        <input type="button" value="��������" onclick="location.href='<?= SITE_ROOT ?>'">
        <input type="submit" value="�����������" onclick="document.cform.input<?= $this->cValue%7 ?>.value='<?= $this->cValue ?>'">
      </form>
