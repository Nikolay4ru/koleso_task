<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { die('Неверный ID письма'); }

$mailbox = imap_open("{192.168.0.8:143/imap/notls}INBOX", "volkov@koleso-russia.ru", "koleso3052222");

function get_part($mbox, $msg_number, $part, $encoding) {
    $data = imap_fetchbody($mbox, $msg_number, $part);
    switch ($encoding) {
        case 0: return $data; // 7BIT
        case 1: return $data; // 8BIT
        case 2: return $data; // BINARY
        case 3: return base64_decode($data); // BASE64
        case 4: return quoted_printable_decode($data); // QUOTED-PRINTABLE
        case 5: return $data; // OTHER
        default: return $data;
    }
}

function get_attachments($structure, $mbox, $msg_number, $part_number = '') {
    $attachments = [];
    if (isset($structure->parts) && count($structure->parts)) {
        foreach ($structure->parts as $i => $part) {
            $part_num = $part_number ? $part_number.'.'.($i+1) : ($i+1);
            if ($part->ifdparameters) {
                foreach ($part->dparameters as $object) {
                    if (strtolower($object->attribute) == 'filename') {
                        $filename = mb_decode_mimeheader($object->value);
                        $attachments[] = [
                            'filename' => $filename,
                            'data' => get_part($mbox, $msg_number, $part_num, $part->encoding),
                        ];
                    }
                }
            }
            // поиск вложений в подчастях (рекурсия)
            if (isset($part->parts)) {
                $attachments = array_merge($attachments, get_attachments($part, $mbox, $msg_number, $part_num));
            }
        }
    }
    return $attachments;
}

function get_body($mbox, $msg_number, $structure, $part_number = '') {
    $body = '';
    if ($structure->type == 1 && isset($structure->parts)) {
        foreach ($structure->parts as $i => $part) {
            $sub_part_number = $part_number ? $part_number.'.'.($i+1) : ($i+1);
            if ($part->type == 0) { // text
                $body = get_part($mbox, $msg_number, $sub_part_number, $part->encoding);
                if ($part->subtype == 'HTML') {
                    return $body;
                }
            } elseif ($part->type == 1) {
                $body = get_body($mbox, $msg_number, $part, $sub_part_number);
            }
        }
    } elseif ($structure->type == 0) {
        $body = get_part($mbox, $msg_number, $part_number ?: 1, $structure->encoding);
        if ($structure->subtype == 'HTML') {
            return $body;
        }
    }
    return $body;
}

$overview = imap_fetch_overview($mailbox, $id, 0)[0];
$structure = imap_fetchstructure($mailbox, $id);

$subject = htmlspecialchars(mb_decode_mimeheader($overview->subject));
$from = htmlspecialchars(mb_decode_mimeheader($overview->from));
$date = $overview->date;

$body = get_body($mailbox, $id, $structure);
if (!$body) $body = '(Нет текста письма)';
if (stripos($body, '<html') === false) $body = nl2br(htmlspecialchars($body));

$attachments = get_attachments($structure, $mailbox, $id);

imap_close($mailbox);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $subject ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7fa; margin: 0; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 6px #0002; }
        .header { padding: 20px 20px 10px 20px; border-bottom: 1px solid #eee; }
        .subject { font-size: 1.3em; margin-bottom: 8px; }
        .info { color: #666; font-size: 0.97em; }
        .body { padding: 20px; }
        .attachments { padding: 20px; border-top: 1px solid #eee; background: #fafbff; }
        .attachments h4 { margin-top: 0; }
        .att-link { display:inline-block; margin-right:10px; }
        .back { display:inline-block; margin:20px; margin-bottom:0; color:#2a5bda; text-decoration:none;}
        .back:hover { text-decoration: underline; }
        iframe { width: 100%; min-height: 400px; border: none; }
    </style>
</head>
<body>
<div class="container">
    <a href="mail_list.php" class="back">&larr; Назад к списку</a>
    <div class="header">
        <div class="subject"><?= $subject ?></div>
        <div class="info">От: <?= $from ?><br>Дата: <?= $date ?></div>
    </div>
    <div class="body"><?= $body ?></div>
    <?php if ($attachments): ?>
    <div class="attachments">
        <h4>Вложения:</h4>
        <?php foreach ($attachments as $i => $att): 
            $filename = htmlspecialchars($att['filename']);
            $url = "mail_view.php?id=$id&att=$i";
        ?>
            <a class="att-link" href="<?= $url ?>" target="_blank"><?= $filename ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php
// Отдача вложения при запросе
if (isset($_GET['att']) && isset($attachments[$_GET['att']])) {
    $att = $attachments[$_GET['att']];
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$att['filename'].'"');
    header('Content-Length: ' . strlen($att['data']));
    echo $att['data'];
    exit;
}
?>
</body>
</html>