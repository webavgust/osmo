<?php


namespace App\Services\Tools;


use App\Models\User;
use App\Services\Notificator\Notifications\AnyEmailNotification;
use App\Services\Notificator\Notifications\EmailNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class Tools
{
    const MONTH_NAME = [
        1 => 'Январь',
        2 => 'Февраль',
        3 => 'Март',
        4 => 'Апрель',
        5 => 'Май',
        6 => 'Июнь',
        7 => 'Июль',
        8 => 'Август',
        9 => 'Сентябрь',
        10 => 'Октябрь',
        11 => 'Ноябрь',
        12 => 'Декабрь',
    ];
    const MONTH_NAME_D = [
        1 => 'Января',
        2 => 'Февраля',
        3 => 'Марта',
        4 => 'Апреля',
        5 => 'Мая',
        6 => 'Июня',
        7 => 'Июля',
        8 => 'Августа',
        9 => 'Сентября',
        10 => 'Октября',
        11 => 'Ноября',
        12 => 'Декабря',
    ];

    /**
     * Получение данных методом POST и расшифровка
     *
     * @param $url
     * @param $data
     * @return mixed
     */
    public static function CurlPostJson($url, $data = [])
    {
        $ch = curl_init();
        //$url = "http://portal-hal2.gk-rte.ru/api/hook";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        //$response = str_replace(chr(239) . chr(187) . chr(191), null, $response);

        curl_close($ch);

        return json_decode($response, 1);
    }

    /**
     * Склонение числительного
     *
     * @param $count
     * @param $ar
     * @param $out_count
     * @return mixed|string
     */
    public function num_rus($count, $ar, $out_count = false)
    {
        $l = substr($count, -1);
        $l2 = (strlen($count) > 1) ? substr($count, -2) : 0;
        $str = $ar[0]; // 22
        if ($l == 1 && ($count < 11 || $count > 20)) $str = $ar[1]; // 21
        if (($l2 >= 11 && $l2 <= 20) || ($l >= 5 && $l <= 9) || $l == 0) $str = $ar[2]; // 20

        if ($out_count) $str = $count . ' ' . $str;

        return $str;
    }

    /**
     * Нормализация цены
     *
     * @param $cost
     * @param $delim
     * @param $out_zeroes
     * @return string
     */
    public function cost_normalize($cost, $delim = '.', $out_zeroes = false, $separator = ' ', $mode = false, $precision = 0)
    {
        switch($mode) {
            case "k":
                $postfix = "k";
                $cost = round($cost / 1000, $precision);
            break;
            case "M":
                $postfix = "M";
                $cost = round($cost / 1000000, $precision);
            break;
            default:
                $postfix = null;
                $cost = round($cost, $precision);
        }
        $parts = explode('.', (string)$cost);

        $ret = trim(strrev(chunk_split(strrev($parts[0]), 3, $separator)));

        if ($out_zeroes) {
            if (empty($parts[1]))
                $parts[1] = '00';
            if (strlen($parts[1]) == 1)
                $parts[1] .= '0';
            if (strlen($parts[1]) > 2) {
                $parts[1] = substr($parts[1], 0, 2);
            }

        }
        if (!empty($parts[1])) $ret .= $delim . $parts[1];

        if($separator !== ' ') {
            return substr($ret, 1) . $postfix;
        } else {
            return $ret . $postfix;
        }
    }


    /**
     * Вывод даны в формате
     *
     * @param $date
     * @param $format
     * @return string
     */
    public function date($date, $format = 'd.m.Y')
    {
        return Carbon::createFromTimestamp(strtotime($date))->format($format);
    }

    /**
     * Дата с полным написанием месяца
     *
     * @param Carbon $carbon
     * @return string
     */
    public function date_full(Carbon $carbon)
    {
        return $carbon->format('d') . ' ' . Str::lower(self::MONTH_NAME_D[$carbon->format('n')]) . ' ' . $carbon->format('Y') . ' г.';
    }

    /**
     * Дата с префиксом от и полным названием месяца в дательном падеже
     *
     * @param $date
     * @return string
     */
    public function date_from($date)
    {
        $carbon = Carbon::createFromTimestamp(strtotime($date));

        return 'от «' . $carbon->format('d') . '»  ' . self::MONTH_NAME_D[$carbon->format('n')] . ' ' . $carbon->format('Y') . ' г.';
    }

    /**
     * Обработчик группы для select2
     *
     * @param $array
     * @param $children_field
     * @param $ar_default
     * @return array
     */
    public static function select2_optgroup($array, $children_field, $ar_default = [])
    {
        $result = [];

        foreach ($array as $item) {
            $row = new \StdClass();
            $row->id = $item->$children_field->pluck('id')->join(";");
            $row->text = $item->name;
            $row->children = [];
            foreach ($item->$children_field as $child) {
                $child_obj = new \stdClass();
                $child_obj->id = $child->id;
                $child_obj->text = $child->fullName;
                if (!empty($ar_default) && in_array($child->id, $ar_default)) $child_obj->selected = true;
                $row->children[] = $child_obj;
            }
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Перевод имени файла в класс
     *
     * @param $filename
     * @return false|string
     */
    public static function filenameToClass($filename)
    {
        $root = Str::before($_SERVER["DOCUMENT_ROOT"], '/public');
        $filename = Str::replace([$root, '.php'], '', $filename);
        $filename = Str::replace(['/app'], 'App', $filename);
        $filename = Str::replace('/', '\\', $filename);
        $filename = '\\' . $filename;

        return (class_exists($filename )) ? $filename : false;
    }

    /**
     * Конвертация кол-ва минут в чч:мм
     *
     * @param $source
     * @return string
     */
    public static function time_convert($source = null)
    {
        if (empty($source)) return '';
        $hours = floor($source / 60);
        $minutes = $source % 60;

        return sprintf('%02d', $hours) . ":" . sprintf('%02d', $minutes);
    }

    /**
     * Перевод чч:мм в кол-во минут
     *
     * @param $source
     * @return int|null
     */
    public function time_convert_back($source = null)
    {
        if (empty($source)) return null;

        $carbon = Carbon::createFromFormat('H:i', $source);
        $start = $carbon->clone()->startOfDay();

        return $carbon->diffInMinutes($start);
    }

    /**
     * Прослойка для подбора пользователя
     *
     * @param $user_id
     * @return User|User[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|\LaravelIdea\Helper\App\Models\_IH_User_C|null
     */
    public function userByID($user_id)
    {
        return \App\Modules\Pub\User\Models\User::find($user_id);
    }

    /**
     * Вспомогательная функция
     *
     * @param $n
     * @param $f1
     * @param $f2
     * @param $f5
     * @return mixed
     */
    private static function morph($n, $f1, $f2, $f5)
    {
        $n = abs($n) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) return $f5;
        if ($n1 > 1 && $n1 < 5) return $f2;
        if ($n1 == 1) return $f1;

        return $f5;
    }

    /**
     * Полная стоимость прописью
     *
     * @param float $cost_total
     * @return string
     */
    public static function cost_full_string(float $cost_total)
    {

        $nol = 'ноль';
        $str[100] = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
        $str[11] = array('', 'десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать', 'двадцать');
        $str[10] = array('', 'десять', 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
        $sex = array(
            array('', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),// m
            array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять') // f
        );
        $forms = array(
            array('копейка', 'копейки', 'копеек', 1), // 10^-2
            array('рубль', 'рубля', 'рублей', 0), // 10^ 0
            array('тысяча', 'тысячи', 'тысяч', 1), // 10^ 3
            array('миллион', 'миллиона', 'миллионов', 0), // 10^ 6
            array('миллиард', 'миллиарда', 'миллиардов', 0), // 10^ 9
            array('триллион', 'триллиона', 'триллионов', 0), // 10^12
        );
        $out = $tmp = [];
        // Поехали!
        $tmp = explode('.', str_replace(',', '.', $cost_total));
        $rub = number_format($tmp[0], 0, '', '-');

        if ($rub == 0) $out[] = $nol;

        // нормализация копеек
        $kop = isset($tmp[1]) ? substr(str_pad($tmp[1], 2, '0', STR_PAD_RIGHT), 0, 2) : '00';

        $segments = explode('-', $rub);
        $offset = sizeof($segments);
        if ((int)$rub == 0) { // если 0 рублей
            $o[] = $nol;
            $o[] = self::morph(0, $forms[1][0], $forms[1][1], $forms[1][2]);
        } else {
            foreach ($segments as $k => $lev) {
                $sexi = (int)$forms[$offset][3]; // определяем род
                $ri = (int)$lev; // текущий сегмент
                if ($ri == 0 && $offset > 1) {// если сегмент==0 & не последний уровень(там Units)
                    $offset--;
                    continue;
                }
                // нормализация
                $ri = str_pad($ri, 3, '0', STR_PAD_LEFT);
                // получаем циферки для анализа
                $r1 = (int)substr($ri, 0, 1); //первая цифра
                $r2 = (int)substr($ri, 1, 1); //вторая
                $r3 = (int)substr($ri, 2, 1); //третья
                $r22 = (int)$r2 . $r3; //вторая и третья
                // разгребаем порядки
                if ($ri > 99) $o[] = $str[100][$r1]; // Сотни
                if ($r22 > 20) {// >20
                    $o[] = $str[10][$r2];
                    $o[] = $sex[$sexi][$r3];
                } else { // <=20
                    if ($r22 > 9) $o[] = $str[11][$r22 - 9]; // 10-20
                    elseif ($r22 > 0) $o[] = $sex[$sexi][$r3]; // 1-9
                }

                // Рубли
                $o[] = self::morph($ri, $forms[$offset][0], $forms[$offset][1], $forms[$offset][2]);
                $offset--;
            }
        }
        $o[] = $kop;
        $o[] = self::morph($kop, $forms[0][0], $forms[0][1], $forms[0][2]);
        $collect = collect($o);
        $collect[0] = Str::ucfirst($collect[0]);


        $right = $collect->pop(3)->reverse();
        $left = $collect;

        return '(' . $left->implode(" ") . ') ' . $right->implode(' ');
    }

    /**
     * Разбивка строки на $parts частей
     *
     * @param string $string
     * @param $parts
     * @return array
     */
    public function str_chunk(string $string, $parts = 2)
    {
        $l = Str::length($string);
        $l_line = $l / $parts;
        $words = explode(" ", $string);
        $lines = [];
        $current_line = 0;
        foreach ($words as $word) {
            $lines[$current_line][] = $word;
            if (Str::length(implode(" ", $lines[$current_line])) > $l_line)
                $current_line++;
        }

        foreach ($lines as $i => $ar) {
            $lines[$i] = implode(" ", $ar);
        }

        return $lines;
    }

    /**
     * Склонение фамилий
     *
     * @param $string
     * @param $case
     * @return mixed|string
     */
    public function surnameRusCase($string, $case = 'd')
    {
        $arTable = [
            -1 => [
                'removeLast' => 1, 'r' => 'ой', 'd' => 'ой', 'v' => 'у', 't' => 'ой', 'p' => 'ой',
            ],
            -2 => [
                'removeLast' => 2, 'r' => 'ой', 'd' => 'ой', 'v' => 'ую', 't' => 'ой', 'p' => 'ой',
            ],
            1 => [
                'r' => 'а', 'd' => 'у', 'v' => 'а', 't' => 'ым', 'p' => 'е',
            ],
            2 => [
                'removeLast' => 2, 'r' => 'ого', 'd' => 'ому', 'v' => 'ого', 't' => 'им', 'p' => 'ом',
            ],
            3 => [
                'removeLast' => 1, 'r' => 'ы', 'd' => 'е', 'v' => 'у', 't' => 'ой', 'p' => 'е',
            ],
        ];

        // анализируем фамилию
        if (empty($type) && Str::endsWith($string, 'ов')) $type = 1; // Иванов
        if (empty($type) && Str::endsWith($string, 'ова')) $type = -1; // Иванова
        if (empty($type) && Str::endsWith($string, 'ский')) $type = 2; // Августиновский
        if (empty($type) && Str::endsWith($string, 'ская')) $type = -2; // Августиновская
        if (empty($type) && Str::endsWith($string, 'цкий')) $type = 2; // Августиновский
        if (empty($type) && Str::endsWith($string, 'цкая')) $type = -2; // Августиновская
        if (empty($type) && Str::endsWith($string, 'ой')) $type = 2; // Лихой
        if (empty($type) && Str::endsWith($string, 'ая')) $type = -2; // Лихая
        if (empty($type) && Str::endsWith($string, 'а')) $type = 3; // Гитара
        if (empty($type) && Str::endsWith($string, 'ин')) $type = 1; // Ильин
        if (empty($type) && Str::endsWith($string, 'ев')) $type = 1; // Васильев
        if (empty($type) && Str::endsWith($string, 'ын')) $type = 1; // Васильев
        if (empty($type) && Str::endsWith($string, 'ый')) $type = 2; // Васильев

        if (empty($type))
            return $string;

        $preset = $arTable[$type];
        if (!empty($preset['removeLast']))
            $string = Str::substr($string, 0, $preset['removeLast'] * -1);

        $string .= $preset[$case];

        return $string;
    }

    /**
     * Склонение имён
     *
     * @param $string
     * @param $case
     * @return mixed|string
     */
    public function nameRusCase($string, $case = 'd')
    {
        $sogl = ['б', 'в', 'г', 'д', 'ж', 'з', 'й', 'к', 'л', 'м', 'н', 'п', 'р', 'с', 'т', 'ф', 'х', 'ц', 'ч', 'ш', 'щ'];
        $arTable = [
            1 => ['removeLast' => 1, 'r' => 'я', 'd' => 'ю', 'v' => 'я', 't' => 'ем', 'p' => 'е'],
            2 => ['r' => 'а', 'd' => 'у', 'v' => 'а', 't' => 'ом', 'p' => 'е'],
            22 => ['removeLast' => 1, 'r' => 'а', 'd' => 'у', 'v' => 'а', 't' => 'ом', 'p' => 'е'],
            3 => ['removeLast' => 1, 'r' => 'ы', 'd' => 'е', 'v' => 'у', 't' => 'ой', 'p' => 'е'],
            4 => ['removeLast' => 1, 'r' => 'и', 'd' => 'е', 'v' => 'ю', 't' => 'ёй', 'p' => 'е'],
            5 => ['removeLast' => 1, 'r' => 'и', 'd' => 'и', 'v' => 'ь', 't' => 'ью', 'p' => 'и'],
        ];

        // анализируем имя

        if (empty($type) && Str::endsWith($string, 'ин')) $type = -1;
        if (empty($type) && Str::endsWith($string, 'о')) $type = -1;
        if (empty($type) && Str::endsWith($string, 'е')) $type = -1;
        if (empty($type) && Str::endsWith($string, 'и')) $type = -1;

        if (empty($type) && in_array(mb_strtolower($string), ['павел'])) {
            $string = mb_substr($string, 0, -2) . mb_substr($string, -1);
            $type = 2;
        }

        if (empty($type) && in_array(Str::substr($string, -1, 1), ['й'])) $type = 1;
        if (empty($type) && in_array(Str::substr($string, -1, 1), $sogl)) $type = 2;

        if (empty($type) && Str::endsWith($string, 'а')) $type = 3;
        if (empty($type) && Str::endsWith($string, 'я')) $type = 4;
        if (empty($type) && Str::endsWith($string, 'ья')) $type = 4;
        if (empty($type) && Str::endsWith($string, 'ия')) $type = 4;
        if (empty($type) && Str::endsWith($string, 'ея')) $type = 4;
        if (empty($type) && Str::endsWith($string, 'ь')) $type = 5;

        if (empty($type) || $type < 0)
            return $string;

        $preset = $arTable[$type];
        if (!empty($preset['removeLast']))
            $string = Str::substr($string, 0, $preset['removeLast'] * -1);

        $string .= $preset[$case];

        return $string;
    }

    /**
     * Склонение отчеств
     *
     * @param $string
     * @param $case
     * @return mixed|string
     */
    public function lastnameRusCase($string, $case = 'd')
    {
        $sogl = ['б', 'в', 'г', 'д', 'ж', 'з', 'й', 'к', 'л', 'м', 'н', 'п', 'р', 'с', 'т', 'ф', 'х', 'ц', 'ч', 'ш', 'щ'];
        $arTable = [
            1 => ['r' => 'а', 'd' => 'у', 'v' => 'а', 't' => 'ем', 'p' => 'е'],
            2 => ['removeLast' => 1, 'r' => 'ы', 'd' => 'е', 'v' => 'у', 't' => 'ой', 'p' => 'е'],

        ];

        // анализируем отчество
        if (empty($type) && Str::endsWith($string, 'вич')) $type = 1;
        if (empty($type) && Str::endsWith($string, 'вна')) $type = 2;

        if (empty($type) || $type < 0)
            return $string;

        $preset = $arTable[$type];
        if (!empty($preset['removeLast']))
            $string = Str::substr($string, 0, $preset['removeLast'] * -1);

        $string .= $preset[$case];

        return $string;
    }

    /**
     * Полное склонение Ф.И.О.
     *
     * @param $surname
     * @param $name
     * @param $lastname
     * @param $case
     * @return string
     */
    public function fullnameRusCase($surname, $name, $lastname = null, $case = 'd')
    {
        $ret[] = $this->surnameRusCase($surname, $case);
        $ret[] = $this->nameRusCase($name, $case);
        if (!empty($lastname)) $ret[] = $this->lastnameRusCase($lastname, $case);

        return implode(" ", $ret);
    }

    /**
     * Вспомогательная функция
     *
     * @param $numberInput
     * @param $fromBaseInput
     * @param $toBaseInput
     * @return int|mixed|string
     */
    private function convBase($numberInput, $fromBaseInput, $toBaseInput)
    {
        if ($fromBaseInput == $toBaseInput) return $numberInput;
        $fromBase = str_split($fromBaseInput, 1);
        $toBase = str_split($toBaseInput, 1);
        $number = str_split($numberInput, 1);
        $fromLen = strlen($fromBaseInput);
        $toLen = strlen($toBaseInput);
        $numberLen = strlen($numberInput);
        $retval = '';
        if ($toBaseInput == '0123456789') {
            $retval = 0;
            for ($i = 1; $i <= $numberLen; $i++)
                $retval = bcadd($retval, bcmul(array_search($number[$i - 1], $fromBase), bcpow($fromLen, $numberLen - $i)));
            return $retval;
        }
        if ($fromBaseInput != '0123456789')
            $base10 = convBase($numberInput, $fromBaseInput, '0123456789');
        else
            $base10 = $numberInput;
        if ($base10 < strlen($toBaseInput))
            return $toBase[$base10];
        while ($base10 != '0') {
            $retval = $toBase[bcmod($base10, $toLen)] . $retval;
            $base10 = bcdiv($base10, $toLen, 0);
        }

        return $retval;
    }

    /**
     * Конвертация числа в буквенный индекс столбца
     *
     * @param $num
     * @return int|mixed|string
     */
    // TODO: bug when num > 255
    public function excelGetColumnByIndex(int $num)
    {
        $ret = [];
        while($num > 0) {
            if($num > 26) {
                $letter = floor($num % 26);  // 15
                $ret[] = $letter;
            } else {
                $ret[] = $num;
                break;
            }

            $num = floor($num / 26);
        }
        $ret_string = "";
        $ret = array_reverse($ret);

        foreach($ret as $letter) {
            $ret_string .= $this->convBase($letter - 1, '0123456789', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        }

        return $ret_string;
    }

    /**
     * Числительное
     *
     * @param int $cost_total
     * @return string
     */
    public static function countable_dat(int $cost_total)
    {
        $nol = 'ноль';
        $str[100] = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
        $str[11] = array('', 'десятого', 'одиннадцатого', 'двенадцатого', 'тринадцатого', 'четырнадцатого', 'пятнадцатого', 'шестнадцатого', 'семнадцатого', 'восемнадцатого', 'девятнадцатого', 'двадцатого');
        $str[-10] = array('', 'десятого', 'двадцатого', 'тридцатого', 'сорокого', 'пятидесятого', 'шестидесятого', 'семидесятого', 'восемидесятого', 'девяностого');
        $str[10] = array('', 'десять', 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
        $sex = array(
            array('', 'певого', 'второго', 'третьего', 'четвёртого', 'пятого', 'шестого', 'седьмого', 'восьмого', 'девятого'),// m
            array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять') // f
        );
        $forms = array(
            array('копейка', 'копейки', 'копеек', 1), // 10^-2
            array('рубль', 'рубля', 'рублей', 0), // 10^ 0
            array('тысяча', 'тысячи', 'тысяч', 1), // 10^ 3
            array('миллион', 'миллиона', 'миллионов', 0), // 10^ 6
            array('миллиард', 'миллиарда', 'миллиардов', 0), // 10^ 9
            array('триллион', 'триллиона', 'триллионов', 0), // 10^12
        );
        $out = $tmp = [];
        // Поехали!
        $tmp = explode('.', str_replace(',', '.', $cost_total));
        $rub = number_format($tmp[0], 0, '', '-');

        if ($rub == 0) $out[] = $nol;

        $segments = explode('-', $rub);
        $offset = sizeof($segments);
        if ((int)$rub == 0) { // если 0 рублей
            return "нулевого";
        } else {
            foreach ($segments as $k => $lev) {
                $sexi = (int)$forms[$offset][3]; // определяем род
                $ri = (int)$lev; // текущий сегмент
                if ($ri == 0 && $offset > 1) {// если сегмент==0 & не последний уровень(там Units)
                    $offset--;
                    continue;
                }
                // нормализация
                $ri = str_pad($ri, 3, '0', STR_PAD_LEFT);
                // получаем циферки для анализа
                $r1 = (int)substr($ri, 0, 1); //первая цифра
                $r2 = (int)substr($ri, 1, 1); //вторая
                $r3 = (int)substr($ri, 2, 1); //третья
                $r22 = (int)$r2 . $r3; //вторая и третья
                // разгребаем порядки
                if ($ri > 99) $o[] = $str[100][$r1]; // Сотни
                if ($r22 > 20) {// >20
                    if ($r3 == 0) {
                        $o[] = $str[-10][$r2];
                    } else {
                        $o[] = $str[10][$r2];
                    }

                    $o[] = $sex[$sexi][$r3];
                } else { // <=20
                    if ($r22 > 9) $o[] = $str[11][$r22 - 9]; // 10-20
                    elseif ($r22 > 0) $o[] = $sex[$sexi][$r3]; // 1-9
                }


                // Рубли
                if ($offset > 1)
                    $o[] = self::morph($ri, $forms[$offset][0], $forms[$offset][1], $forms[$offset][2]);
                $offset--;
            }
        }

        return implode(" ", $o);
    }

    public static function strOpposite($string)
    {
        $eng = ['q','w','e','r','t','y','u','i','o','p','[',']','a','s','d','f','g','h','j','k','l',';','\'','\\','z','x','c','v','b','n','m',',','.'];
        $rus = ['й','ц','у','к','е','н','г','ш','щ','з','х','ъ','ф','ы','в','а','п','р','о','л','д','ж','э','\\','я','ч','с','м','и','т','ь','б','ю'];
        $engCount = $rusCount = 0;
        $result = '';

        // определим язык
        for($i = 0; $i < Str::length($string); $i++) {
            $char = Str::substr($string, $i, 1);
            if(in_array($char, $eng)) $engCount++;
            if(in_array($char, $rus)) $rusCount++;
        }

        $from = $engCount > $rusCount ? $eng : $rus;
        $to = $engCount > $rusCount ? $rus : $eng;

        for($i = 0; $i < Str::length($string); $i++) {
            $char = Str::substr($string, $i, 1);

            $pos = array_search($char, $from);
            $result .= $to[$pos] ?? '';
        }

        return $result;
    }

    public static function getRelationshipsForEloquentlModel($eloquentObject) {
        $methods = get_class_methods($eloquentObject);

        $relations = [];
        foreach ($methods as $method) {

            if(in_array($method , ['getModuleName'])) {
                return $relations;
            }
            try {
                $reflection = new \ReflectionMethod($eloquentObject, $method);
                //filter out non-eloquent relationship methods that expect parameters in
                //their signature (else they blow up when they get called below without pars)
                $pars = $reflection->getNumberOfParameters();
                if ($pars == 0) {
                    $possibleRelationship = $eloquentObject->$method();
                    if(gettype($possibleRelationship) !== 'object') continue;
                    //one of the things we can use to distinctively identify an eloquent
                    //relationship method (BelongsTo, HasMany...etc) is to check for
                    //one of the public methods defined in Illuminate/Database/Eloquent/Relations/Relation.php
                    //(and hope that it is not discontinued/removed in future versions of Laravel :))

                    if (method_exists($possibleRelationship, "getEager")) {
                        $relationshipType = get_class($possibleRelationship);

                        //remove namespace
                        if ($pos = strrpos($relationshipType, '\\')) {
                            $relationshipType = substr($relationshipType, $pos + 1);
                        }

                        $relations[$method] = $relationshipType;
                    }
                }
            } catch (\Exception $ex) {
                //Eloquent's save() method will throw some
                //sql error because $eloquentObject may be
                //an empty object like new App\User (so some NOT NULL db fields may blow up)
                continue;
            }
        }
        dd($relations);

        return $relations;
    }


    function parseNumberFromString($input) {
        // Убираем все нецифровые символы, кроме точек, запятых и минусов
        $cleaned = preg_replace('/[^\d\.,\-]/', '', $input);

        // Если строка пустая после очистки, возвращаем 0
        if (empty($cleaned)) {
            return 0;
        }

        // Определяем, является ли число отрицательным
        $isNegative = (strpos($cleaned, '-') !== false);
        $cleaned = str_replace('-', '', $cleaned);

        // Считаем количество точек и запятых
        $dotCount = substr_count($cleaned, '.');
        $commaCount = substr_count($cleaned, ',');

        // Если есть и точки, и запятые - скорее всего запятые это разделители тысяч, а точка - десятичный разделитель
        if ($dotCount > 0 && $commaCount > 0) {
            $cleaned = str_replace(',', '', $cleaned);
        }
        // Если только запятые - проверяем, если запятая на 3-й позиции с конца, то это разделитель тысяч
        elseif ($commaCount > 0) {
            // Если последняя запятая находится на позиции, где обычно бывает десятичный разделитель
            $lastCommaPos = strrpos($cleaned, ',');
            $charsAfterLastComma = strlen($cleaned) - $lastCommaPos - 1;

            if ($charsAfterLastComma === 2 || $charsAfterLastComma === 3) {
                // Запятая используется как десятичный разделитель
                $cleaned = str_replace(',', '.', $cleaned);
                // Убираем остальные запятые (разделители тысяч)
                $cleaned = str_replace(',', '', $cleaned);
            } else {
                // Все запятые - разделители тысяч
                $cleaned = str_replace(',', '', $cleaned);
            }
        }
        // Если только точки - аналогичная логика
        elseif ($dotCount > 1) {
            // Если точек несколько, то все кроме последней - разделители тысяч
            $lastDotPos = strrpos($cleaned, '.');
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = substr($cleaned, 0, $lastDotPos - ($dotCount - 1)) . '.' . substr($cleaned, $lastDotPos - ($dotCount - 1));
        }

        // Преобразуем в число
        $result = floatval($cleaned);

        // Применяем отрицательный знак если нужно
        if ($isNegative) {
            $result = -$result;
        }

        // Если число целое, возвращаем как integer, иначе как double
        return $result == (int)$result ? (int)$result : (float)$result;
    }




}
