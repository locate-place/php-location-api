<?php

/*
 * This file is part of the locate-place/php-location-api project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Constants\DB;

/**
 * Class StopWord
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-05-25)
 * @since 0.1.0 (2024-05-25) First version.
 */
class StopWord
{
    /* See: https://github.com/postgres/postgres/blob/master/src/backend/snowball/stopwords/german.stop */
    final public const DE = [
        'aber',
        'alle',
        'allem',
        'allen',
        'aller',
        'alles',
        'als',
        'also',
        'am',
        'an',
        'ander',
        'andere',
        'anderem',
        'anderen',
        'anderer',
        'anderes',
        'anderm',
        'andern',
        'anderr',
        'anders',
        'auch',
        'auf',
        'aus',
        'bei',
        'bin',
        'bis',
        'bist',
        'da',
        'damit',
        'dann',
        'der',
        'den',
        'des',
        'dem',
        'die',
        'das',
        'daß',
        'derselbe',
        'derselben',
        'denselben',
        'desselben',
        'demselben',
        'dieselbe',
        'dieselben',
        'dasselbe',
        'dazu',
        'dein',
        'deine',
        'deinem',
        'deinen',
        'deiner',
        'deines',
        'denn',
        'derer',
        'dessen',
        'dich',
        'dir',
        'du',
        'dies',
        'diese',
        'diesem',
        'diesen',
        'dieser',
        'dieses',
        'doch',
        'dort',
        'durch',
        'ein',
        'eine',
        'einem',
        'einen',
        'einer',
        'eines',
        'einig',
        'einige',
        'einigem',
        'einigen',
        'einiger',
        'einiges',
        'einmal',
        'er',
        'ihn',
        'ihm',
        'es',
        'etwas',
        'euer',
        'eure',
        'eurem',
        'euren',
        'eurer',
        'eures',
        'für',
        'gegen',
        'gewesen',
        'hab',
        'habe',
        'haben',
        'hat',
        'hatte',
        'hatten',
        'hier',
        'hin',
        'hinter',
        'ich',
        'mich',
        'mir',
        'ihr',
        'ihre',
        'ihrem',
        'ihren',
        'ihrer',
        'ihres',
        'euch',
        'im',
        'in',
        'indem',
        'ins',
        'ist',
        'jede',
        'jedem',
        'jeden',
        'jeder',
        'jedes',
        'jene',
        'jenem',
        'jenen',
        'jener',
        'jenes',
        'jetzt',
        'kann',
        'kein',
        'keine',
        'keinem',
        'keinen',
        'keiner',
        'keines',
        'können',
        'könnte',
        'machen',
        'man',
        'manche',
        'manchem',
        'manchen',
        'mancher',
        'manches',
        'mein',
        'meine',
        'meinem',
        'meinen',
        'meiner',
        'meines',
        'mit',
        'muss',
        'musste',
        'nach',
        'nicht',
        'nichts',
        'noch',
        'nun',
        'nur',
        'ob',
        'oder',
        'ohne',
        'sehr',
        'sein',
        'seine',
        'seinem',
        'seinen',
        'seiner',
        'seines',
        'selbst',
        'sich',
        'sie',
        'ihnen',
        'sind',
        'so',
        'solche',
        'solchem',
        'solchen',
        'solcher',
        'solches',
        'soll',
        'sollte',
        'sondern',
        'sonst',
        'über',
        'um',
        'und',
        'uns',
        'unse',
        'unsem',
        'unsen',
        'unser',
        'unses',
        'unter',
        'viel',
        'vom',
        'von',
        'vor',
        'während',
        'war',
        'waren',
        'warst',
        'was',
        'weg',
        'weil',
        'weiter',
        'welche',
        'welchem',
        'welchen',
        'welcher',
        'welches',
        'wenn',
        'werde',
        'werden',
        'wie',
        'wieder',
        'will',
        'wir',
        'wird',
        'wirst',
        'wo',
        'wollen',
        'wollte',
        'würde',
        'würden',
        'zu',
        'zum',
        'zur',
        'zwar',
        'zwischen',
    ];

    /* See: https://github.com/postgres/postgres/blob/master/src/backend/snowball/stopwords/english.stop */
    final public const EN = [
        'i',
        'me',
        'my',
        'myself',
        'we',
        'our',
        'ours',
        'ourselves',
        'you',
        'your',
        'yours',
        'yourself',
        'yourselves',
        'he',
        'him',
        'his',
        'himself',
        'she',
        'her',
        'hers',
        'herself',
        'it',
        'its',
        'itself',
        'they',
        'them',
        'their',
        'theirs',
        'themselves',
        'what',
        'which',
        'who',
        'whom',
        'this',
        'that',
        'these',
        'those',
        'am',
        'is',
        'are',
        'was',
        'were',
        'be',
        'been',
        'being',
        'have',
        'has',
        'had',
        'having',
        'do',
        'does',
        'did',
        'doing',
        'a',
        'an',
        'the',
        'and',
        'but',
        'if',
        'or',
        'because',
        'as',
        'until',
        'while',
        'of',
        'at',
        'by',
        'for',
        'with',
        'about',
        'against',
        'between',
        'into',
        'through',
        'during',
        'before',
        'after',
        'above',
        'below',
        'to',
        'from',
        'up',
        'down',
        'in',
        'out',
        'on',
        'off',
        'over',
        'under',
        'again',
        'further',
        'then',
        'once',
        'here',
        'there',
        'when',
        'where',
        'why',
        'how',
        'all',
        'any',
        'both',
        'each',
        'few',
        'more',
        'most',
        'other',
        'some',
        'such',
        'no',
        'nor',
        'not',
        'only',
        'own',
        'same',
        'so',
        'than',
        'too',
        'very',
        's',
        't',
        'can',
        'will',
        'just',
        'don',
        'should',
        'now',
    ];

    /* See: https://github.com/postgres/postgres/blob/master/src/backend/snowball/stopwords/spanish.stop */
    final public const ES = [
        'de',
        'la',
        'que',
        'el',
        'en',
        'y',
        'a',
        'los',
        'del',
        'se',
        'las',
        'por',
        'un',
        'para',
        'con',
        'no',
        'una',
        'su',
        'al',
        'lo',
        'como',
        'más',
        'pero',
        'sus',
        'le',
        'ya',
        'o',
        'este',
        'sí',
        'porque',
        'esta',
        'entre',
        'cuando',
        'muy',
        'sin',
        'sobre',
        'también',
        'me',
        'hasta',
        'hay',
        'donde',
        'quien',
        'desde',
        'todo',
        'nos',
        'durante',
        'todos',
        'uno',
        'les',
        'ni',
        'contra',
        'otros',
        'ese',
        'eso',
        'ante',
        'ellos',
        'e',
        'esto',
        'mí',
        'antes',
        'algunos',
        'qué',
        'unos',
        'yo',
        'otro',
        'otras',
        'otra',
        'él',
        'tanto',
        'esa',
        'estos',
        'mucho',
        'quienes',
        'nada',
        'muchos',
        'cual',
        'poco',
        'ella',
        'estar',
        'estas',
        'algunas',
        'algo',
        'nosotros',
        'mi',
        'mis',
        'tú',
        'te',
        'ti',
        'tu',
        'tus',
        'ellas',
        'nosotras',
        'vosostros',
        'vosostras',
        'os',
        'mío',
        'mía',
        'míos',
        'mías',
        'tuyo',
        'tuya',
        'tuyos',
        'tuyas',
        'suyo',
        'suya',
        'suyos',
        'suyas',
        'nuestro',
        'nuestra',
        'nuestros',
        'nuestras',
        'vuestro',
        'vuestra',
        'vuestros',
        'vuestras',
        'esos',
        'esas',
        'estoy',
        'estás',
        'está',
        'estamos',
        'estáis',
        'están',
        'esté',
        'estés',
        'estemos',
        'estéis',
        'estén',
        'estaré',
        'estarás',
        'estará',
        'estaremos',
        'estaréis',
        'estarán',
        'estaría',
        'estarías',
        'estaríamos',
        'estaríais',
        'estarían',
        'estaba',
        'estabas',
        'estábamos',
        'estabais',
        'estaban',
        'estuve',
        'estuviste',
        'estuvo',
        'estuvimos',
        'estuvisteis',
        'estuvieron',
        'estuviera',
        'estuvieras',
        'estuviéramos',
        'estuvierais',
        'estuvieran',
        'estuviese',
        'estuvieses',
        'estuviésemos',
        'estuvieseis',
        'estuviesen',
        'estando',
        'estado',
        'estada',
        'estados',
        'estadas',
        'estad',
        'he',
        'has',
        'ha',
        'hemos',
        'habéis',
        'han',
        'haya',
        'hayas',
        'hayamos',
        'hayáis',
        'hayan',
        'habré',
        'habrás',
        'habrá',
        'habremos',
        'habréis',
        'habrán',
        'habría',
        'habrías',
        'habríamos',
        'habríais',
        'habrían',
        'había',
        'habías',
        'habíamos',
        'habíais',
        'habían',
        'hube',
        'hubiste',
        'hubo',
        'hubimos',
        'hubisteis',
        'hubieron',
        'hubiera',
        'hubieras',
        'hubiéramos',
        'hubierais',
        'hubieran',
        'hubiese',
        'hubieses',
        'hubiésemos',
        'hubieseis',
        'hubiesen',
        'habiendo',
        'habido',
        'habida',
        'habidos',
        'habidas',
        'soy',
        'eres',
        'es',
        'somos',
        'sois',
        'son',
        'sea',
        'seas',
        'seamos',
        'seáis',
        'sean',
        'seré',
        'serás',
        'será',
        'seremos',
        'seréis',
        'serán',
        'sería',
        'serías',
        'seríamos',
        'seríais',
        'serían',
        'era',
        'eras',
        'éramos',
        'erais',
        'eran',
        'fui',
        'fuiste',
        'fue',
        'fuimos',
        'fuisteis',
        'fueron',
        'fuera',
        'fueras',
        'fuéramos',
        'fuerais',
        'fueran',
        'fuese',
        'fueses',
        'fuésemos',
        'fueseis',
        'fuesen',
        'sintiendo',
        'sentido',
        'sentida',
        'sentidos',
        'sentidas',
        'siente',
        'sentid',
        'tengo',
        'tienes',
        'tiene',
        'tenemos',
        'tenéis',
        'tienen',
        'tenga',
        'tengas',
        'tengamos',
        'tengáis',
        'tengan',
        'tendré',
        'tendrás',
        'tendrá',
        'tendremos',
        'tendréis',
        'tendrán',
        'tendría',
        'tendrías',
        'tendríamos',
        'tendríais',
        'tendrían',
        'tenía',
        'tenías',
        'teníamos',
        'teníais',
        'tenían',
        'tuve',
        'tuviste',
        'tuvo',
        'tuvimos',
        'tuvisteis',
        'tuvieron',
        'tuviera',
        'tuvieras',
        'tuviéramos',
        'tuvierais',
        'tuvieran',
        'tuviese',
        'tuvieses',
        'tuviésemos',
        'tuvieseis',
        'tuviesen',
        'teniendo',
        'tenido',
        'tenida',
        'tenidos',
        'tenidas',
        'tened',
    ];
}
