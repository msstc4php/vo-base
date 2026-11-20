<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Fixtures;

/**
 * Example IBANs of the SWIFT IBAN Registry (edition in docs/sources/IBAN.md) with the bank and branch identifiers
 * and the SEPA flag the registry gives for them. For BA, PL and SE the registry's identifier examples disagree
 * with its own IBAN example (BA: taken from another BBAN; PL: bank example empty; SE: wrong bank), so the
 * identifiers here are read from the registry's identifier positions.
 */
final class IbanRegistryExamples
{
    /**
     * @return array<string, array{non-empty-string, non-empty-string, non-empty-string|null, bool}> country => IBAN,
     *                                                                                               bank identifier, branch identifier, SEPA flag
     */
    public static function all(): array
    {
        return [
            'AD' => ['AD1200012030200359100100', '0001', '2030', true],
            'AE' => ['AE070331234567890123456', '033', null, false],
            'AL' => ['AL47212110090000000235698741', '212', '11009', false],
            'AT' => ['AT611904300234573201', '19043', null, true],
            'AZ' => ['AZ21NABZ00000000137010001944', 'NABZ', null, false],
            'BA' => ['BA391290079401028494', '129', '007', false],
            'BE' => ['BE68539007547034', '539', null, true],
            'BG' => ['BG80BNBG96611020345678', 'BNBG', '9661', true],
            'BH' => ['BH67BMAG00001299123456', 'BMAG', null, false],
            'BI' => ['BI4210000100010000332045181', '10000', '10001', false],
            'BR' => ['BR1800360305000010009795493C1', '00360305', '00001', false],
            'BY' => ['BY13NBRB3600900000002Z00AB00', 'NBRB', null, false],
            'CH' => ['CH9300762011623852957', '00762', null, true],
            'CR' => ['CR05015202001026284066', '0152', null, false],
            'CY' => ['CY17002001280000001200527600', '002', '00128', true],
            'CZ' => ['CZ6508000000192000145399', '0800', null, true],
            'DE' => ['DE89370400440532013000', '37040044', null, true],
            'DJ' => ['DJ2100010000000154000100186', '00010', '00000', false],
            'DK' => ['DK5000400440116243', '0040', null, true],
            'DO' => ['DO28BAGR00000001212453611324', 'BAGR', null, false],
            'EE' => ['EE382200221020145685', '22', null, true],
            'EG' => ['EG380019000500000000263180002', '0019', '0005', false],
            'ES' => ['ES9121000418450200051332', '2100', '0418', true],
            'FI' => ['FI2112345600000785', '123', null, true],
            'FK' => ['FK88SC123456789012', 'SC', null, false],
            'FO' => ['FO6264600001631634', '6460', null, false],
            'FR' => ['FR1420041010050500013M02606', '20041', null, true],
            'GB' => ['GB29NWBK60161331926819', 'NWBK', '601613', true],
            'GE' => ['GE29NB0000000101904917', 'NB', null, false],
            'GI' => ['GI75NWBK000000007099453', 'NWBK', null, true],
            'GL' => ['GL8964710001000206', '6471', null, false],
            'GR' => ['GR1601101250000000012300695', '011', '0125', true],
            'GT' => ['GT82TRAJ01020000001210029690', 'TRAJ', null, false],
            'HN' => ['HN88CABF00000000000250005469', 'CABF', null, false],
            'HR' => ['HR1210010051863000160', '1001005', null, true],
            'HU' => ['HU42117730161111101800000000', '117', '7301', true],
            'IE' => ['IE29AIBK93115212345678', 'AIBK', '931152', true],
            'IL' => ['IL620108000000099999999', '010', '800', false],
            'IQ' => ['IQ98NBIQ850123456789012', 'NBIQ', '850', false],
            'IS' => ['IS140159260076545510730339', '01', '59', true],
            'IT' => ['IT60X0542811101000000123456', '05428', '11101', true],
            'JO' => ['JO94CBJO0010000000000131000302', 'CBJO', '0010', false],
            'KW' => ['KW81CBKU0000000000001234560101', 'CBKU', null, false],
            'KZ' => ['KZ86125KZT5004100100', '125', null, false],
            'LB' => ['LB62099900000001001901229114', '0999', null, false],
            'LC' => ['LC55HEMM000100010012001200023015', 'HEMM', null, false],
            'LI' => ['LI21088100002324013AA', '08810', null, true],
            'LT' => ['LT121000011101001000', '10000', null, true],
            'LU' => ['LU280019400644750000', '001', null, true],
            'LV' => ['LV80BANK0000435195001', 'BANK', null, true],
            'LY' => ['LY83002048000020100120361', '002', '048', false],
            'MC' => ['MC5811222000010123456789030', '11222', '00001', true],
            'MD' => ['MD24AG000225100013104168', 'AG', null, false],
            'ME' => ['ME25505000012345678951', '505', null, false],
            'MK' => ['MK07250120000058984', '250', null, false],
            'MN' => ['MN121234123456789123', '1234', null, false],
            'MR' => ['MR1300020001010000123456753', '00020', '00101', false],
            'MT' => ['MT84MALT011000012345MTLCAST001S', 'MALT', '01100', true],
            'MU' => ['MU17BOMM0101101030300200000MUR', 'BOMM01', '01', false],
            'NI' => ['NI45BAPR00000013000003558124', 'BAPR', null, false],
            'NL' => ['NL91ABNA0417164300', 'ABNA', null, true],
            'NO' => ['NO9386011117947', '8601', null, true],
            'OM' => ['OM810180000001299123456', '018', null, false],
            'PK' => ['PK36SCBL0000001123456702', 'SCBL', null, false],
            'PL' => ['PL61109010140000071219812874', '10901014', null, true],
            'PS' => ['PS92PALS000000000400123456702', 'PALS', null, false],
            'PT' => ['PT50000201231234567890154', '0002', null, true],
            'QA' => ['QA58DOHB00001234567890ABCDEFG', 'DOHB', null, false],
            'RO' => ['RO49AAAA1B31007593840000', 'AAAA', null, true],
            'RS' => ['RS35260005601001611379', '260', null, false],
            'RU' => ['RU0304452522540817810538091310419', '044525225', '40817', false],
            'SA' => ['SA0380000000608010167519', '80', null, false],
            'SC' => ['SC18SSCB11010000000000001497USD', 'SSCB11', '01', false],
            'SD' => ['SD2129010501234001', '29', null, false],
            'SE' => ['SE4550000000058398257466', '500', null, true],
            'SI' => ['SI56263300012039086', '26330', null, true],
            'SK' => ['SK3112000000198742637541', '1200', null, true],
            'SM' => ['SM86U0322509800000000270100', '03225', '09800', true],
            'SO' => ['SO211000001001000100141', '1000', '001', false],
            'ST' => ['ST23000100010051845310146', '0001', '0001', false],
            'SV' => ['SV62CENR00000000000000700025', 'CENR', null, false],
            'TL' => ['TL380080012345678910157', '008', null, false],
            'TN' => ['TN5910006035183598478831', '10', '006', false],
            'TR' => ['TR330006100519786457841326', '00061', null, false],
            'UA' => ['UA213223130000026007233566001', '322313', null, false],
            'VA' => ['VA59001123000012345678', '001', null, true],
            'VG' => ['VG96VPVG0000012345678901', 'VPVG', null, false],
            'XK' => ['XK051212012345678906', '12', '12', false],
            'YE' => ['YE15CBYE0001018861234567891234', 'CBYE', '0001', false],
        ];
    }
}
