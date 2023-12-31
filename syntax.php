<?php

use dokuwiki\Extension\SyntaxPlugin;

/**
 * DokuWiki Plugin number (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Vincent Tscherter <vincent@tscherter.net>
 */
class syntax_plugin_number extends SyntaxPlugin
{
    /** @inheritDoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritDoc */
    public function getPType()
    {
        return 'normal';
    }

    /** @inheritDoc */
    public function getSort()
    {
        return 100;
    }

    /** @inheritDoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('{{n>.*?}}', $mode, 'plugin_number');
    }

    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        
        $raw = substr($match, 4, -2);
        $raw = strtolower($raw);
        $raw = preg_replace('/[^0-9a-z_]/', '', $raw);

        $data = [];
        $data['raw'] = $raw;

        if (preg_match('/^\d+(e\d+)?$/', $raw)) {
            $data['type'] = 'dec';
            $data['value'] = intval($raw);
        } else if (preg_match('/^0b[01]+$/', $raw)) {
            $data['type'] = 'bin';
            $data['value'] = intval(substr($raw, 2), 2);
        } else if (preg_match('/^0x[0-9a-f]+$/i', $raw)) {
            $data['type'] = 'hex';
            $data['value'] = intval(substr($raw, 2), 16);
        } else if (preg_match('/^[0-9a-z]+_[0-9]+$/i', $raw)) {
            $data['type'] = 'padic';
            list($number, $base) = explode('_', $raw);
            $data['value'] = intval($number, $base);
            $data['number'] = $number;
            $data['base'] = intval($base);
            if ($data['base'] < 2 || $data['base'] > 36 || base_convert($data['value'], 10, $data['base']) != $number)
                $data['value'] = NAN;
        } else {
            $data['value'] = NAN;
        }
        return $data;
    }

    static private function renderDec($value)
    {
        return '<code style="color: blue">'.$value.'</code>';
    }

    static private function renderHex($value)
    { 
        return '<code style="color: blue"><span style="color: red">0x</span>' 
            . (is_string($value) ? substr($value, 2) : dechex($value)) . '</code>';
    }

    static private function renderBin($value)
    {
        return '<code style="color: blue"><span style="color: red">0b</span>'
            . (is_string($value) ? substr($value, 2) : decbin($value)) . '</code>';
    }

    const NUMBERSYSTEM = ';;Binary;Ternary;Quaternary;Quinary;Senary;Septenary;Octal;Nonary;Decimal;Undecimal;Duodecimal;Tridecimal;Tetradecimal;Pentadecimal;Hexadecimal;Heptadecimal;Octodecimal;Enneadecimal;Vigesimal;Unvigesimal;Duovigesimal;Trivigesimal;Tetravigesimal;Pentavigesimal;Hexavigesimal;Heptavigesimal;Octovigesimal;Enneavigesimal;Trigesimal;Untrigesimal;Duotrigesimal;Tritrigesimal;Tetratrigesimal;Pentatrigesimal; Hexatrigesimal';

    /** @inheritDoc */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode !== 'xhtml') {
            return false;
        }
        $value = $data['value'];
        if (is_nan($value)) {
            $renderer->doc .= '<code style="color: red">⚠️ warning: ' . $data['raw'] . ' is not a valid input</code>';
            return true;
        } 
        if (!is_int($value) || $value >= PHP_INT_MAX) {
            $renderer->doc .= '<code style="color: red">⚠️ warning: ' . $data['raw'] . ' is not a safe for conversion </code>'.$value;
            return true;
        }
        $type = $data['type'];
        if ($type == 'dec') {
            $number = self::renderDec($data['raw']);
            $tooltip = '<strong>Decimal number</strong> (base 10)<br>= '
                . self::renderBin($value) . ' (binary)';
            if ("~$value" != '~' . $data['raw']) { // 
                $tooltip .= '<br>= ' . self::renderDec($value) . ' (decimal)';
            }
            $tooltip .= '<br>= ' . self::renderHex($value) . ' (hexadecimal)';
        } else if ($type == 'bin') {
            $number = self::renderBin($data['raw']);
            $tooltip = '<strong>Binary number</strong> (base 2)<br>= '
                . self::renderDec($value) . ' (decimal)<br>=  '
                . self::renderHex($value) . ' (hexadecimal)';
        } else if ($type == 'hex') {
            $number = self::renderHex($data['raw']);
            $tooltip = '<strong>Hexadecimal number</strong> (base 16)<br>= '
                . self::renderBin($value) . ' (binary)<br>= '
                . self::renderDec($value) . ' (decimal)';
        } else if ($type == 'padic') {
            $number = '<code style="color: blue"><span style="color: grey">[</span>' 
                . $data['number'] 
                . '<span style="color: grey">]</span><sub style="color: red">' 
                . $data['base'] . '</sub></code>';
            $tooltip = '<strong>'
                . explode(';', self::NUMBERSYSTEM)[$data['base']]
                . ' number</strong> (base ' . $data['base'] . ')<br>= '
                . self::renderBin($value) . ' (binary)<br>= '
                . self::renderDec($value) . ' (decimal)<br>= '
                . self::renderHex($value) . ' (hexadecimal)';
        }
        // 
        $renderer->doc .= "<span class='plugin-number'><span class='plugin-number-tooltip'>$tooltip</span>$number</span>";

        return true;
    }
}