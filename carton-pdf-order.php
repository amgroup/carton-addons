<?php
/*
Plugin Name: CartOn PDF ORDER
Plugin URI: #
Description: A plugin to make pdf order.
Version: 1.0
Author: Andrey Guryev
Author URI: #
*/

define( 'CARTON_PDF_ORDER_DIR', plugin_dir_path(__FILE__) );
define( 'CARTON_PDF_ORDER_CLASSES_DIR', CARTON_PDF_ORDER_DIR . 'carton/classes/' );
define( 'CARTON_PDF_ORDER_TEMPLATES_DIR', CARTON_PDF_ORDER_DIR . 'carton/templates/xslt/' );
define( 'CARTON_PDF_ORDER_TMP_DIR', '/tmp/' );


function append_carton_pdf_order_plugin() {
    global $carton;

    include_once( CARTON_PDF_ORDER_CLASSES_DIR . 'class-carton-pdf-order.php' );

    $pdf = new Carton_PDF_Order();
}
add_action('plugins_loaded', 'append_carton_pdf_order_plugin' );


// OTHER USEFUL FUNCTIONS
/*
 * Function allow to get html headers from plain html response as array
*/
if( !function_exists( 'html_headers_array' ) ) {
	function html_headers_array( $headers ) {
		$struct = array();

		foreach ( preg_split("(\r\n)",$headers) as $header ) {
			if ( $header ) {
				$pair = preg_split( "(: ?)", $header );
				if ( isset($pair[1]) ) {
					$struct[ $pair[0] ] = $pair[1];
					$struct[ strtolower($pair[0]) ] = $pair[1];
				}
			}
		}
		return $struct;
	}
}

/*
 * Function append xml chunk to DOM
*/
/*
if( !function_exists( 'appendChunk' ) ) {
    function appendChunk(SimpleXMLElement $parent, SimpleXMLElement $chunk) {
        $_parent = dom_import_simplexml($parent);
        $_chunk  = dom_import_simplexml($chunk);
        $_parent->appendChild($_parent->ownerDocument->importNode($_chunk, true));
    }
}
*/
function sxml_append(SimpleXMLElement $to, SimpleXMLElement $from) {
    $toDom = dom_import_simplexml($to);
    $fromDom = dom_import_simplexml($from);
    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
}

/*
 * Function converts html chars to xml chars
*/
if( !function_exists( 'html2xml_charachters' ) ) {
	function html2xml_charachters ( $xml ) {
        $xml = preg_replace ( '/(&nbsp;)/i', '&#160;', $xml ); // неразрывный пробел
        $xml = preg_replace ( '/(&ensp;)/i', '&#8194;', $xml ); // узкий пробел (еn-шириной в букву n)
        $xml = preg_replace ( '/(&emsp;)/i', '&#8195;', $xml ); // широкий пробел (em-шириной в букву m)
        $xml = preg_replace ( '/(&ndash;)/i', '&#8211;', $xml ); // узкое тире (en-тире)
        $xml = preg_replace ( '/(&mdash;)/i', '&#8212;', $xml ); // широкое тире (em -тире)
        $xml = preg_replace ( '/(&shy;)/i', '&#173;', $xml ); // мягкий перенос
        $xml = preg_replace ( '/(&copy;)/i', '&#169;', $xml ); // копирайт
        $xml = preg_replace ( '/(&reg;)/i', '&#174;', $xml ); // знак зарегистрированной торговой марки
        $xml = preg_replace ( '/(&trade;)/i', '&#8482;', $xml ); // знак торговой марки
        $xml = preg_replace ( '/(&ordm;)/i', '&#186;', $xml ); // копье Марса
        $xml = preg_replace ( '/(&ordf;)/i', '&#170;', $xml ); // зеркало Венеры
        $xml = preg_replace ( '/(&permil;)/i', '&#8240;', $xml ); // промилле
        $xml = preg_replace ( '/(&pi;)/i', '&#960;', $xml ); // пи (используйте Times New Roman)
        $xml = preg_replace ( '/(&brvbar;)/i', '&#166;', $xml ); // вертикальный пунктир
        $xml = preg_replace ( '/(&sect;)/i', '&#167;', $xml ); // параграф
        $xml = preg_replace ( '/(&deg;)/i', '&#176;', $xml ); // градус
        $xml = preg_replace ( '/(&micro;)/i', '&#181;', $xml ); // знак "микро"
        $xml = preg_replace ( '/(&para;)/i', '&#182;', $xml ); // знак абзаца
        $xml = preg_replace ( '/(&hellip;|&#8230;)/i', '…', $xml ); // многоточие
        $xml = preg_replace ( '/(&oline;)/i', '&#8254;', $xml ); // надчеркивание
        $xml = preg_replace ( '/(&acute;)/i', '&#180;', $xml ); // знак ударения
        $xml = preg_replace ( '/(&times;)/i', '&#215;', $xml ); // умножить
        $xml = preg_replace ( '/(&divide;)/i', '&#247;', $xml ); // разделить
        $xml = preg_replace ( '/(&lt;)/i', '&#60;', $xml ); // меньше
        $xml = preg_replace ( '/(&gt;)/i', '&#62;', $xml ); // больше
        $xml = preg_replace ( '/(&plusmn;)/i', '&#177;', $xml ); // плюс/минус
        $xml = preg_replace ( '/(&sup1;)/i', '&#185;', $xml ); // степень 1
        $xml = preg_replace ( '/(&sup2;)/i', '&#178;', $xml ); // степень 2
        $xml = preg_replace ( '/(&sup3;)/i', '&#179;', $xml ); // степень 3
        $xml = preg_replace ( '/(&not;)/i', '&#172;', $xml ); // отрицание
        $xml = preg_replace ( '/(&frac14;)/i', '&#188;', $xml ); // одна четвертая
        $xml = preg_replace ( '/(&frac12;)/i', '&#189;', $xml ); // одна вторая
        $xml = preg_replace ( '/(&frac34;)/i', '&#190;', $xml ); // три четверти
        $xml = preg_replace ( '/(&frasl;)/i', '&#8260;', $xml ); // дробная черта
        $xml = preg_replace ( '/(&minus;)/i', '&#8722;', $xml ); // минус
        $xml = preg_replace ( '/(&le;)/i', '&#8804;', $xml ); // меньше или равно
        $xml = preg_replace ( '/(&ge;)/i', '&#8805;', $xml ); // больше или равно
        $xml = preg_replace ( '/(&asymp;)/i', '&#8776;', $xml ); // приблизительно (почти) равно
        $xml = preg_replace ( '/(&ne;)/i', '&#8800;', $xml ); // не равно
        $xml = preg_replace ( '/(&equiv;)/i', '&#8801;', $xml ); // тождественно
        $xml = preg_replace ( '/(&radic;)/i', '&#8730;', $xml ); // квадратный корень (радикал)
        $xml = preg_replace ( '/(&infin;)/i', '&#8734;', $xml ); // бесконечность
        $xml = preg_replace ( '/(&sum;)/i', '&#8721;', $xml ); // знак суммирования
        $xml = preg_replace ( '/(&prod;)/i', '&#8719;', $xml ); // знак произведения
        $xml = preg_replace ( '/(&part;)/i', '&#8706;', $xml ); // частичный дифференциал
        $xml = preg_replace ( '/(&int;)/i', '&#8747;', $xml ); // интеграл
        $xml = preg_replace ( '/(&forall;)/i', '&#8704;', $xml ); // для всех (видно только если жирным шрифтом)
        $xml = preg_replace ( '/(&exist;)/i', '&#8707;', $xml ); // существует
        $xml = preg_replace ( '/(&empty;)/i', '&#8709;', $xml ); // пустое множество
        $xml = preg_replace ( '/(&Oslash;)/i', '&#216;', $xml ); // диаметр
        $xml = preg_replace ( '/(&isin;)/i', '&#8712;', $xml ); // принадлежит
        $xml = preg_replace ( '/(&notin;)/i', '&#8713;', $xml ); // не принадлежит
        $xml = preg_replace ( '/(&ni;)/i', '&#8727;', $xml ); // содержит
        $xml = preg_replace ( '/(&sub;)/i', '&#8834;', $xml ); // является подмножеством
        $xml = preg_replace ( '/(&sup;)/i', '&#8835;', $xml ); // является надмножеством
        $xml = preg_replace ( '/(&nsub;)/i', '&#8836;', $xml ); // не является подмножеством
        $xml = preg_replace ( '/(&sube;)/i', '&#8838;', $xml ); // является подмножеством либо равно
        $xml = preg_replace ( '/(&supe;)/i', '&#8839;', $xml ); // является надмножеством либо равно
        $xml = preg_replace ( '/(&oplus;)/i', '&#8853;', $xml ); // плюс в кружке
        $xml = preg_replace ( '/(&otimes;)/i', '&#8855;', $xml ); // знак умножения в кружке
        $xml = preg_replace ( '/(&perp;)/i', '&#8869;', $xml ); // перпендикулярно
        $xml = preg_replace ( '/(&ang;)/i', '&#8736;', $xml ); // угол
        $xml = preg_replace ( '/(&and;)/i', '&#8743;', $xml ); // логическое И
        $xml = preg_replace ( '/(&or;)/i', '&#8744;', $xml ); // логическое ИЛИ
        $xml = preg_replace ( '/(&cap;)/i', '&#8745;', $xml ); // пересечение
        $xml = preg_replace ( '/(&cup;)/i', '&#8746;', $xml ); // объединение
        $xml = preg_replace ( '/(&euro;)/i', '&#8364;', $xml ); // Евро
        $xml = preg_replace ( '/(&cent;)/i', '&#162;', $xml ); // Цент
        $xml = preg_replace ( '/(&pound;)/i', '&#163;', $xml ); // Фунт
        $xml = preg_replace ( '/(&current;)/i', '&#164;', $xml ); // Знак валюты
        $xml = preg_replace ( '/(&yen;)/i', '&#165;', $xml ); // Знак йены и юаня
        $xml = preg_replace ( '/(&fnof;)/i', '&#402;', $xml ); // Знак флорина
        $xml = preg_replace ( '/(&bull;)/i', '&#8226;', $xml ); // простой маркер
        $xml = preg_replace ( '/(&middot;)/i', '&#183;', $xml ); // средняя точка
        $xml = preg_replace ( '/(&spades;)/i', '&#9824;', $xml ); // пики
        $xml = preg_replace ( '/(&clubs;)/i', '&#9827;', $xml ); // трефы
        $xml = preg_replace ( '/(&hearts;)/i', '&#9829;', $xml ); // червы
        $xml = preg_replace ( '/(&diams;)/i', '&#9830;', $xml ); // бубны
        $xml = preg_replace ( '/(&loz;)/i', '&#9674;', $xml ); // ромб
        $xml = preg_replace ( '/(&quot;)/i', '&#34;', $xml ); // двойная кавычка
        $xml = preg_replace ( '/(&amp;)/i', '&#38;', $xml ); // амперсанд
        $xml = preg_replace ( '/(&laquo;)/i', '&#171;', $xml ); // левая типографская кавычка (кавычка-елочка)
        $xml = preg_replace ( '/(&raquo;)/i', '&#187;', $xml ); // правая типографская кавычка (кавычка-елочка)
        $xml = preg_replace ( '/(&prime;)/i', '&#8242;', $xml ); // штрих (минуты, футы)
        $xml = preg_replace ( '/(&Prime;)/i', '&#8243;', $xml ); // двойной штрих (секунды, дюймы)
        $xml = preg_replace ( '/(&lsquo;)/i', '&#8216;', $xml ); // левая верхняя одинарная кавычка
        $xml = preg_replace ( '/(&rsquo;)/i', '&#8217;', $xml ); // правая верхняя одинарная кавычка
        $xml = preg_replace ( '/(&sbquo;)/i', '&#8218;', $xml ); // правая нижняя одинарная кавычка
        $xml = preg_replace ( '/(&ldquo;)/i', '&#8220;', $xml ); // кавычка-лапка левая
        $xml = preg_replace ( '/(&rdquo;)/i', '&#8221;', $xml ); // кавычка-лапка правая верхняя
        $xml = preg_replace ( '/(&bdquo;)/i', '&#8222;', $xml ); // кавычка-лапка правая нижняя
        $xml = preg_replace ( '/(&larr;)/i', '&#8592;', $xml ); // стрелка влево
        $xml = preg_replace ( '/(&uarr;)/i', '&#8593;', $xml ); // стрелка вверх
        $xml = preg_replace ( '/(&rarr;)/i', '&#8594;', $xml ); // стрелка вправо
        $xml = preg_replace ( '/(&darr;)/i', '&#8595;', $xml ); // стрелка вниз
        $xml = preg_replace ( '/(&harr;)/i', '&#8596;', $xml ); // стрелка влево и вправо
        $xml = preg_replace ( '/(&lArr;)/i', '&#8656;', $xml ); // двойная стрелка влево
        $xml = preg_replace ( '/(&uArr;)/i', '&#8657;', $xml ); // двойная стрелка вверх
        $xml = preg_replace ( '/(&rArr;)/i', '&#8658;', $xml ); // двойная стрелка вправо
        $xml = preg_replace ( '/(&dArr;)/i', '&#8659;', $xml ); // двойная стрелка вниз
        $xml = preg_replace ( '/(&hArr;)/i', '&#8660;', $xml ); // двойная стрелка влево и вправо


        $xml = preg_replace ( '/(&forall;)/i', '&#8704;', $xml ); // квантор всеобщности
        $xml = preg_replace ( '/(&part;)/i', '&#8706;', $xml ); // дифференциал
        $xml = preg_replace ( '/(&exists;)/i', '&#8707;', $xml ); // квантор существования
        $xml = preg_replace ( '/(&empty;)/i', '&#8709;', $xml ); // пустое множество
        $xml = preg_replace ( '/(&nabla;)/i', '&#8711;', $xml ); // набла
        $xml = preg_replace ( '/(&isin;)/i', '&#8712;', $xml ); // принадлежность множеству
        $xml = preg_replace ( '/(&notin;)/i', '&#8713;', $xml ); // непринадлежность множеству
        $xml = preg_replace ( '/(&ni;)/i', '&#8715;', $xml ); // является членом
        $xml = preg_replace ( '/(&prod;)/i', '&#8719;', $xml ); // произведение
        $xml = preg_replace ( '/(&sum;)/i', '&#8721;', $xml ); // сумма
        $xml = preg_replace ( '/(&minus;)/i', '&#8722;', $xml ); // минус
        $xml = preg_replace ( '/(&lowast;)/i', '&#8727;', $xml ); // оператор звездочка
        $xml = preg_replace ( '/(&radic;)/i', '&#8730;', $xml ); // радикал
        $xml = preg_replace ( '/(&prop;)/i', '&#8733;', $xml ); // пропорционально
        $xml = preg_replace ( '/(&infin;)/i', '&#8734;', $xml ); // бесконечность
        $xml = preg_replace ( '/(&ang;)/i', '&#8736;', $xml ); // угол
        $xml = preg_replace ( '/(&and;)/i', '&#8743;', $xml ); // логическое И
        $xml = preg_replace ( '/(&or;)/i', '&#8744;', $xml ); // логическое ИЛИ
        $xml = preg_replace ( '/(&cap;)/i', '&#8745;', $xml ); // пересечение
        $xml = preg_replace ( '/(&cup;)/i', '&#8746;', $xml ); // объединение
        $xml = preg_replace ( '/(&int;)/i', '&#8747;', $xml ); // интеграл
        $xml = preg_replace ( '/(&there4;)/i', '&#8756;', $xml ); // следовательно
        $xml = preg_replace ( '/(&sim;)/i', '&#8764;', $xml ); // тильда
        $xml = preg_replace ( '/(&cong;)/i', '&#8773;', $xml ); // приблизительно равно
        $xml = preg_replace ( '/(&asymp;)/i', '&#8776;', $xml ); // асимптотически равно
        $xml = preg_replace ( '/(&ne;)/i', '&#8800;', $xml ); // не равно
        $xml = preg_replace ( '/(&equiv;)/i', '&#8801;', $xml ); // тождественно равно
        $xml = preg_replace ( '/(&le;)/i', '&#8804;', $xml ); // меньше или равно
        $xml = preg_replace ( '/(&ge;)/i', '&#8805;', $xml ); // больше или равно
        $xml = preg_replace ( '/(&sub;)/i', '&#8834;', $xml ); // подмножество
        $xml = preg_replace ( '/(&sup;)/i', '&#8835;', $xml ); // надмножество
        $xml = preg_replace ( '/(&nsub;)/i', '&#8836;', $xml ); // не подмножество
        $xml = preg_replace ( '/(&sube;)/i', '&#8838;', $xml ); // подмножество или равно
        $xml = preg_replace ( '/(&supe;)/i', '&#8839;', $xml ); // надмножество или равно
        $xml = preg_replace ( '/(&oplus;)/i', '&#8853;', $xml ); // прямая сумма
        $xml = preg_replace ( '/(&otimes;)/i', '&#8855;', $xml ); // векторное произведение
        $xml = preg_replace ( '/(&perp;)/i', '&#8869;', $xml ); // перпендикулярно
        $xml = preg_replace ( '/(&sdot;)/i', '&#8901;', $xml ); // опетор точка
        $xml = preg_replace ( '/(&Alpha;)/i', '&#913;', $xml ); // Прописная альфа
        $xml = preg_replace ( '/(&Beta;)/i', '&#914;', $xml ); // Прописная бета
        $xml = preg_replace ( '/(&Gamma;)/i', '&#915;', $xml ); // Прописная гамма
        $xml = preg_replace ( '/(&Delta;)/i', '&#916;', $xml ); // Прописная дельта
        $xml = preg_replace ( '/(&Epsilon;)/i', '&#917;', $xml ); // Прописная эпсилон
        $xml = preg_replace ( '/(&Zeta;)/i', '&#918;', $xml ); // Прописная дзета
        $xml = preg_replace ( '/(&Eta;)/i', '&#919;', $xml ); // Прописная эта
        $xml = preg_replace ( '/(&Theta;)/i', '&#920;', $xml ); // Прописная тэта
        $xml = preg_replace ( '/(&Iota;)/i', '&#921;', $xml ); // Прописная иота
        $xml = preg_replace ( '/(&Kappa;)/i', '&#922;', $xml ); // Прописная каппа
        $xml = preg_replace ( '/(&Lambda;)/i', '&#923;', $xml ); // Прописная лямбда
        $xml = preg_replace ( '/(&Mu;)/i', '&#924;', $xml ); // Прописная мю
        $xml = preg_replace ( '/(&Nu;)/i', '&#925;', $xml ); // Прописная ню
        $xml = preg_replace ( '/(&Xi;)/i', '&#926;', $xml ); // Прописная кси
        $xml = preg_replace ( '/(&Omicron;)/i', '&#927;', $xml ); // Прописная омикрон
        $xml = preg_replace ( '/(&Pi;)/i', '&#928;', $xml ); // Прописная пи
        $xml = preg_replace ( '/(&Rho;)/i', '&#929;', $xml ); // Прописная ро
        $xml = preg_replace ( '/(&Sigma;)/i', '&#931;', $xml ); // Прописная сигма
        $xml = preg_replace ( '/(&Tau;)/i', '&#932;', $xml ); // Прописная тау
        $xml = preg_replace ( '/(&Upsilon;)/i', '&#933;', $xml ); // Прописная ипсилон
        $xml = preg_replace ( '/(&Phi;)/i', '&#934;', $xml ); // Прописная фи
        $xml = preg_replace ( '/(&Chi;)/i', '&#935;', $xml ); // Прописная хи
        $xml = preg_replace ( '/(&Psi;)/i', '&#936;', $xml ); // Прописная пси
        $xml = preg_replace ( '/(&Omega;)/i', '&#937;', $xml ); // Прописная омега
        $xml = preg_replace ( '/(&alpha;)/i', '&#945;', $xml ); // Строчная альфа
        $xml = preg_replace ( '/(&beta;)/i', '&#946;', $xml ); // Строчная бета
        $xml = preg_replace ( '/(&gamma;)/i', '&#947;', $xml ); // Строчная гамма
        $xml = preg_replace ( '/(&delta;)/i', '&#948;', $xml ); // Строчная дельта
        $xml = preg_replace ( '/(&epsilon;)/i', '&#949;', $xml ); // Строчная эпсилон
        $xml = preg_replace ( '/(&zeta;)/i', '&#950;', $xml ); // Строчная дзета
        $xml = preg_replace ( '/(&eta;)/i', '&#951;', $xml ); // Строчная эта
        $xml = preg_replace ( '/(&theta;)/i', '&#952;', $xml ); // Строчная тета
        $xml = preg_replace ( '/(&iota;)/i', '&#953;', $xml ); // Строчная иота
        $xml = preg_replace ( '/(&kappa;)/i', '&#954;', $xml ); // Строчная каппа
        $xml = preg_replace ( '/(&lambda;)/i', '&#955;', $xml ); // Строчная лямбда
        $xml = preg_replace ( '/(&mu;)/i', '&#956;', $xml ); // Строчная мю
        $xml = preg_replace ( '/(&nu;)/i', '&#957;', $xml ); // Строчная ню
        $xml = preg_replace ( '/(&xi;)/i', '&#958;', $xml ); // Строчная кси
        $xml = preg_replace ( '/(&omicron;)/i', '&#959;', $xml ); // Строчная омикрон
        $xml = preg_replace ( '/(&pi;)/i', '&#960;', $xml ); // Строчная пи
        $xml = preg_replace ( '/(&rho;)/i', '&#961;', $xml ); // Строчная ро
        $xml = preg_replace ( '/(&sigmaf;)/i', '&#962;', $xml ); // Строчная сигма конечная
        $xml = preg_replace ( '/(&sigma;)/i', '&#963;', $xml ); // Строчная сигма
        $xml = preg_replace ( '/(&tau;)/i', '&#964;', $xml ); // Строчная тау
        $xml = preg_replace ( '/(&upsilon;)/i', '&#965;', $xml ); // Строчная ипсилон
        $xml = preg_replace ( '/(&phi;)/i', '&#966;', $xml ); // Строчная фи
        $xml = preg_replace ( '/(&chi;)/i', '&#967;', $xml ); // Строчная хи
        $xml = preg_replace ( '/(&psi;)/i', '&#968;', $xml ); // Строчная пси
        $xml = preg_replace ( '/(&omega;)/i', '&#969;', $xml ); // Строчная омега
        $xml = preg_replace ( '/(&thetasym;)/i', '&#977;', $xml ); // Символ тета
        $xml = preg_replace ( '/(&upsih;)/i', '&#978;', $xml ); // Символ ипсилон
        $xml = preg_replace ( '/(&piv;)/i', '&#982;', $xml ); // Символ пи
        $xml = preg_replace ( '/(&OElig;)/i', '&#338;', $xml ); // латинская заглавная лигатура OE
        $xml = preg_replace ( '/(&oelig;)/i', '&#339;', $xml ); // латинская строчная лигатура OE
        $xml = preg_replace ( '/(&Scaron;)/i', '&#352;', $xml ); // заглавная S с «птичкой»
        $xml = preg_replace ( '/(&scaron;)/i', '&#353;', $xml ); // строчная s с «птичкой»
        $xml = preg_replace ( '/(&Yuml;)/i', '&#376;', $xml ); // заглавная Y с диарезисом
        $xml = preg_replace ( '/(&fnof;)/i', '&#402;', $xml ); // курсивная f (знак математической функции)
        $xml = preg_replace ( '/(&circ;)/i', '&#710;', $xml ); // диакритический знак над гласной
        $xml = preg_replace ( '/(&tilde;)/i', '&#732;', $xml ); // маленькая тильда
        $xml = preg_replace ( '/(&ensp;)/i', '&#8194;', $xml ); // пробел в половину em
        $xml = preg_replace ( '/(&emsp;)/i', '&#8195;', $xml ); // пробел размером в em
        $xml = preg_replace ( '/(&thinsp;)/i', '&#8201;', $xml ); // узкий пробел
        $xml = preg_replace ( '/(&lrm;)/i', '&#8206;', $xml ); // направление вывода текста слева направо
        $xml = preg_replace ( '/(&rlm;)/i', '&#8207;', $xml ); // направление вывода текста справа налево
        $xml = preg_replace ( '/(&ndash;)/i', '&#8211;', $xml ); // тире в половину em
        $xml = preg_replace ( '/(&mdash;)/i', '&#8212;', $xml ); // длинное тире (целый em)
        $xml = preg_replace ( '/(&lsquo;)/i', '&#8216;', $xml ); // левая одиночная кавычка
        $xml = preg_replace ( '/(&rsquo;)/i', '&#8217;', $xml ); // правая одиночная кавычка
        $xml = preg_replace ( '/(&sbquo;)/i', '&#8218;', $xml ); // нижняя кавычка
        $xml = preg_replace ( '/(&ldquo;)/i', '&#8220;', $xml ); // левая двойная кавычка
        $xml = preg_replace ( '/(&rdquo;)/i', '&#8221;', $xml ); // правая двойная кавычка
        $xml = preg_replace ( '/(&bdquo;)/i', '&#8222;', $xml ); // двойная нижняя кавычка
        $xml = preg_replace ( '/(&dagger;)/i', '&#8224;', $xml ); // крест
        $xml = preg_replace ( '/(&Dagger;)/i', '&#8225;', $xml ); // двойной крест
        $xml = preg_replace ( '/(&bull;)/i', '&#8226;', $xml ); // булет (закрашеный кружок)
        $xml = preg_replace ( '/(&hellip;)/i', '&#8230;', $xml ); // троеточие
        $xml = preg_replace ( '/(&permil;)/i', '&#8240;', $xml ); // промилле
        $xml = preg_replace ( '/(&prime;)/i', '&#8242;', $xml ); // минуты
        $xml = preg_replace ( '/(&Prime;)/i', '&#8243;', $xml ); // секунды
        $xml = preg_replace ( '/(&lsaquo;)/i', '&#8249;', $xml ); // одиночная левая кавычка
        $xml = preg_replace ( '/(&rsaquo;)/i', '&#8250;', $xml ); // одиночная правая кавычка
        $xml = preg_replace ( '/(&oline;)/i', '&#8254;', $xml ); // надчеркивание
        $xml = preg_replace ( '/(&euro;)/i', '&#8364;', $xml ); // знак евро
        $xml = preg_replace ( '/(&trade;)/i', '&#8482;', $xml ); // торговая марка
        $xml = preg_replace ( '/(&larr;)/i', '&#8592;', $xml ); // стрелка налево
        $xml = preg_replace ( '/(&uarr;)/i', '&#8593;', $xml ); // стрелка вверх
        $xml = preg_replace ( '/(&rarr;)/i', '&#8594;', $xml ); // стрелка направо
        $xml = preg_replace ( '/(&darr;)/i', '&#8595;', $xml ); // стрелка вниз
        $xml = preg_replace ( '/(&harr;)/i', '&#8596;', $xml ); // стрелка налево-направо
        $xml = preg_replace ( '/(&crarr;)/i', '&#8629;', $xml ); // стрелка перевода каретки (аналог Enter на клавиатуре)
        $xml = preg_replace ( '/(&lceil;)/i', '&#8968;', $xml ); // левый верхний угол
        $xml = preg_replace ( '/(&rceil;)/i', '&#8969;', $xml ); // правый верхний угол
        $xml = preg_replace ( '/(&lfloor;)/i', '&#8970;', $xml ); // левый нижний угол
        $xml = preg_replace ( '/(&rfloor;)/i', '&#8971;', $xml ); // правый нижний угол
        $xml = preg_replace ( '/(&loz;)/i', '&#9674;', $xml ); // ромб
        $xml = preg_replace ( '/(&spades;)/i', '&#9824;', $xml ); // масть «пики»
        $xml = preg_replace ( '/(&clubs;)/i', '&#9827;', $xml ); // масть «трефы»
        $xml = preg_replace ( '/(&hearts;)/i', '&#9829;', $xml ); // масть «червы»
		return $xml;
	}
}

/** Prettifies an XML string into a human-readable and indented work of art 
 *  @param string $xml The XML as a string 
 *  @param boolean $html_output True if the output should be escaped (for use in HTML) 
 */
if( !function_exists( 'xmlpp' ) ) { 
	function xmlpp($xml, $html_output=false) {  
		$xml_obj = new SimpleXMLElement($xml);  
		$level = 4;  
		$indent = 0; // current indentation level  
		$pretty = array();  
		  
		// get an array containing each XML element  
		$xml = explode("\n", preg_replace('/>\s*</', ">\n<", $xml_obj->asXML()));  
	  
		// shift off opening XML tag if present  
		if (count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {  
		  $pretty[] = array_shift($xml);  
		}  
	  
		foreach ($xml as $el) {  
		  if (preg_match('/^<([\w])+[^>\/]*>$/U', $el)) {  
			  // opening tag, increase indent  
			  $pretty[] = str_repeat(' ', $indent) . $el;  
			  $indent += $level;  
		  } else {  
			if (preg_match('/^<\/.+>$/', $el)) {              
			  $indent -= $level;  // closing tag, decrease indent  
			}  
			if ($indent < 0) {  
			  $indent += $level;  
			}  
			$pretty[] = str_repeat(' ', $indent) . $el;  
		  }  
		}     
		$xml = implode("\n", $pretty);     
		return ($html_output) ? htmlentities($xml) : $xml;  
	}
}

?>
