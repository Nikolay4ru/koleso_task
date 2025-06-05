<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$mailbox = imap_open("{192.168.0.8:143/imap/notls}INBOX", "volkov@koleso-russia.ru", "koleso3052222");
$max_emails = 20;
$emails = [];

if ($mailbox) {
    $mails_ids = imap_search($mailbox, 'ALL');
    if ($mails_ids) {
        rsort($mails_ids);
        $mails_ids = array_slice($mails_ids, 0, $max_emails);
        $overviews = imap_fetch_overview($mailbox, implode(',', $mails_ids), 0);
        foreach ($overviews as $overview) {
            $emails[] = [
                'id'      => $overview->msgno,
                'subject' => htmlspecialchars(mb_decode_mimeheader($overview->subject)),
                'from'    => htmlspecialchars(mb_decode_mimeheader($overview->from)),
                'date'    => $overview->date,
            ];
        }
    }
    imap_close($mailbox);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Почта</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7fa; margin: 0; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 6px #0002; }
        h2 { padding: 20px; margin: 0; border-bottom: 1px solid #eee; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #eee; padding: 10px 16px; }
        th { background: #f0f0f5; text-align: left; }
        tr:hover { background: #f5f9ff; }
        a { color: #2a5bda; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>Список писем</h2>
    <table>
        <thead>
            <tr>
                <th>Тема</th>
                <th>От</th>
                <th>Дата</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($emails as $email): ?>
            <tr>
                <td><a href="mail_view.php?id=<?= $email['id'] ?>"><?= $email['subject'] ?></a></td>
                <td><?= $email['from'] ?></td>
                <td><?= $email['date'] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$emails): ?>
            <tr><td colspan="3">Нет писем.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>