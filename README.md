# bbb-mp4
BigBlueButton Record Process

# �������

## extractCursorEvents
    php extractCursorEvents.php --events-file-src=events.xml --events-file-dst=events.new.xml > cursor.events

������� �� ����� events.xml ������� ����������� �������, �������� ����� ���� events.xml ��� ���. ��������� �� �������
��������� � stdout ��� CSV "timestamp,x,y"

## generateCursorPng
    php generateCursorPng.php --src=./cursor.events --dst=./cursor/ --width=1280 --height=720 --diameter=10

������� �� ���� ����� ������� ������� � ������� CSV ������������������ ������ � ������� PNG � ������������ �������.
�������� �����, ���� ����� ��������� ������������������ �����������, �� ������ � ������ � ������ ����� �������

# ���� (��� �� ������� ������)
## NotesWindow
## BroadcastWindow
## PresentationWindow
���� ������ �������
## VideoDock
## ChatWindow
���� ����
## UsersWindow
������ �������������
## ViewersWindow
## ListenersWindow