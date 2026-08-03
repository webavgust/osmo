<?php


namespace App\Facades;


use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;

class Tools extends Facade
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

    public static function morph($n, $f1, $f2, $f5) {
        $n = abs($n) % 100;
        $n1= $n % 10;
        if ($n>10 && $n<20)	return $f5;
        if ($n1>1 && $n1<5)	return $f2;
        if ($n1==1)		return $f1;
        return $f5;
    }

    public static function cost_full_string(float $cost_total)
    {

        $nol = 'ноль';
        $str[100]= array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот', 'восемьсот','девятьсот');
        $str[11] = array('','десять','одиннадцать','двенадцать','тринадцать', 'четырнадцать','пятнадцать','шестнадцать','семнадцать', 'восемнадцать','девятнадцать','двадцать');
        $str[10] = array('','десять','двадцать','тридцать','сорок','пятьдесят', 'шестьдесят','семьдесят','восемьдесят','девяносто');
        $sex = array(
            array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),// m
            array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять') // f
        );
        $forms = array(
            array('копейка',  'копейки',   'копеек',     1), // 10^-2
            array('рубль',    'рубля',     'рублей',     0), // 10^ 0
            array('тысяча',   'тысячи',    'тысяч',      1), // 10^ 3
            array('миллион',  'миллиона',  'миллионов',  0), // 10^ 6
            array('миллиард', 'миллиарда', 'миллиардов', 0), // 10^ 9
            array('триллион', 'триллиона', 'триллионов', 0), // 10^12
        );
        $out = $tmp = [];
        // Поехали!
        $tmp = explode('.', str_replace(',', '.', $cost_total));
        $rub = number_format($tmp[0],0,'','-');

        if ($rub==0) $out[] = $nol;

        // нормализация копеек
        $kop = isset($tmp[1]) ? substr(str_pad($tmp[1], 2, '0', STR_PAD_RIGHT),0,2) : '00';

        $segments = explode('-', $rub);
        $offset = sizeof($segments);
        if ((int)$rub==0) { // если 0 рублей
            $o[] = $nol;
            $o[] = self::morph(0, $forms[1][0],$forms[1][1],$forms[1][2]);
        }
        else {
            foreach ($segments as $k=>$lev) {
                $sexi= (int) $forms[$offset][3]; // определяем род
                $ri  = (int) $lev; // текущий сегмент
                if ($ri==0 && $offset>1) {// если сегмент==0 & не последний уровень(там Units)
                    $offset--;
                    continue;
                }
                // нормализация
                $ri = str_pad($ri, 3, '0', STR_PAD_LEFT);
                // получаем циферки для анализа
                $r1 = (int)substr($ri,0,1); //первая цифра
                $r2 = (int)substr($ri,1,1); //вторая
                $r3 = (int)substr($ri,2,1); //третья
                $r22= (int)$r2.$r3; //вторая и третья
                // разгребаем порядки
                if ($ri>99) $o[] = $str[100][$r1]; // Сотни
                if ($r22>20) {// >20
                    $o[] = $str[10][$r2];
                    $o[] = $sex[ $sexi ][$r3];
                }
                else { // <=20
                    if ($r22>9) $o[] = $str[11][$r22-9]; // 10-20
                    elseif($r22>0)  $o[] = $sex[ $sexi ][$r3]; // 1-9
                }


                // Рубли
                $o[] = self::morph($ri, $forms[$offset][0],$forms[$offset][1],$forms[$offset][2]);
                $offset--;

            }
        }

        $o[] = $kop;
        $o[] = self::morph($kop,$forms[0][0],$forms[0][1],$forms[0][2]);
        $collect = collect($o);
        $collect[0] = Str::ucfirst($collect[0]);
        $b = $collect->pop(2);
        return '('.$collect->implode(" ").') ' . $b->implode(" ");
    }

    protected static function getFacadeAccessor() { return 'tools'; }
}
