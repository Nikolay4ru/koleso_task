<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';

// test_xlsb.php - тест поддержки XLSB

echo "<h2>Тест поддержки XLSB файлов</h2>\n";

// 1. Проверяем PhpSpreadsheet
echo "<h3>1. Проверка PhpSpreadsheet</h3>\n";

if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
    echo "✅ PhpSpreadsheet установлен<br>\n";
    
    // Проверяем поддержку XLSB
    try {
        // Правильный способ проверки поддерживаемых форматов в PhpSpreadsheet
        $availableReaders = [];
        
        // Проверяем основные типы читателей
        $readerTypes = ['Xlsx', 'Xls', 'Xlsb', 'Csv', 'Html', 'Pdf'];
        
        foreach ($readerTypes as $type) {
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($type);
                $availableReaders[] = $type;
            } catch (\Exception $e) {
                // Этот тип не поддерживается
            }
        }
        
        echo "Доступные форматы: " . implode(', ', $availableReaders) . "<br>\n";
        
        if (in_array('Xlsb', $availableReaders)) {
            echo "✅ XLSB reader поддерживается<br>\n";
            
            // Пытаемся создать XLSB reader
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('xlsb');
            echo "✅ XLSB reader создан успешно<br>\n";
            echo "Класс reader: " . get_class($reader) . "<br>\n";
            
        } else {
            echo "❌ XLSB reader НЕ поддерживается<br>\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Ошибка при проверке XLSB reader: " . $e->getMessage() . "<br>\n";
    }
    
} else {
    echo "❌ PhpSpreadsheet НЕ установлен<br>\n";
    echo "<div class='alert alert-warning'>";
    echo "<strong>Для установки выполните:</strong><br>";
    echo "<code>composer require phpoffice/phpspreadsheet</code>";
    echo "</div>\n";
}

// 2. Проверяем альтернативные способы
echo "<h3>2. Проверка альтернативных методов</h3>\n";

// LibreOffice
if (function_exists('exec')) {
    $output = [];
    $return_var = 0;
    exec("which libreoffice 2>/dev/null", $output, $return_var);
    
    if ($return_var === 0) {
        echo "✅ LibreOffice доступен: " . implode(', ', $output) . "<br>\n";
    } else {
        echo "❌ LibreOffice недоступен<br>\n";
    }
} else {
    echo "❌ Функция exec() отключена<br>\n";
}

// 3. Проверяем настройки PHP
echo "<h3>3. Настройки PHP</h3>\n";

$memoryLimit = ini_get('memory_limit');
echo "Memory limit: $memoryLimit<br>\n";

$maxExecutionTime = ini_get('max_execution_time');
echo "Max execution time: {$maxExecutionTime}s<br>\n";

$uploadMaxFilesize = ini_get('upload_max_filesize');
echo "Upload max filesize: $uploadMaxFilesize<br>\n";

// 4. Создаем тестовый XLSB файл (если PhpSpreadsheet доступен)
if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    echo "<h3>4. Создание тестового XLSB файла</h3>\n";
    
    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Заполняем тестовыми данными
        $sheet->setCellValue('A1', 'Тест');
        $sheet->setCellValue('B1', 'XLSB');
        $sheet->setCellValue('C1', 'Файла');
        $sheet->setCellValue('A2', 'Строка 1');
        $sheet->setCellValue('B2', 123.45);
        $sheet->setCellValue('C2', '=B2*2');
        $sheet->setCellValue('A3', 'Строка 2');
        $sheet->setCellValue('B3', 'Текст');
        $sheet->setCellValue('C3', date('Y-m-d'));
        
        // Сохраняем как XLSB
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsb($spreadsheet);
        $testFile = __DIR__ . '/test_xlsb_file.xlsb';
        
        $writer->save($testFile);
        echo "✅ Тестовый XLSB файл создан: $testFile<br>\n";
        echo "Размер файла: " . filesize($testFile) . " байт<br>\n";
        
        // Пытаемся прочитать созданный файл
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsb');
        $testSpreadsheet = $reader->load($testFile);
        $testSheet = $testSpreadsheet->getActiveSheet();
        
        echo "✅ Файл успешно прочитан<br>\n";
        echo "Содержимое A1: " . $testSheet->getCell('A1')->getValue() . "<br>\n";
        echo "Содержимое B2: " . $testSheet->getCell('B2')->getValue() . "<br>\n";
        echo "Содержимое C2: " . $testSheet->getCell('C2')->getCalculatedValue() . "<br>\n";
        
        // Удаляем тестовый файл
        unlink($testFile);
        echo "✅ Тестовый файл удален<br>\n";
        
        // Очищаем память
        $spreadsheet->disconnectWorksheets();
        $testSpreadsheet->disconnectWorksheets();
        
    } catch (Exception $e) {
        echo "❌ Ошибка создания/чтения XLSB: " . $e->getMessage() . "<br>\n";
    }
}

// 5. Рекомендации
echo "<h3>5. Рекомендации</h3>\n";

if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
    echo "<div class='alert alert-danger'>";
    echo "<h5>Критически важно:</h5>";
    echo "<p>Для полной поддержки XLSB файлов необходимо установить PhpSpreadsheet:</p>";
    echo "<pre>composer require phpoffice/phpspreadsheet</pre>";
    echo "</div>\n";
} else {
    echo "<div class='alert alert-success'>";
    echo "<h5>Отлично!</h5>";
    echo "<p>PhpSpreadsheet установлен и готов для работы с XLSB файлами.</p>";
    echo "</div>\n";
}

// Проверяем лимиты
$memoryInBytes = $this->parseMemoryLimit($memoryLimit);
if ($memoryInBytes < 256 * 1024 * 1024) { // Меньше 256MB
    echo "<div class='alert alert-warning'>";
    echo "<h5>Рекомендация по памяти:</h5>";
    echo "<p>Для обработки больших XLSB файлов рекомендуется увеличить memory_limit до 512M:</p>";
    echo "<pre>memory_limit = 512M</pre>";
    echo "</div>\n";
}

function parseMemoryLimit($limit) {
    $unit = strtolower(substr($limit, -1));
    $value = (int) substr($limit, 0, -1);
    
    switch ($unit) {
        case 'g': return $value * 1024 * 1024 * 1024;
        case 'm': return $value * 1024 * 1024;
        case 'k': return $value * 1024;
        default: return $value;
    }
}

echo "<br><h2>Тестирование завершено!</h2>\n";
?>