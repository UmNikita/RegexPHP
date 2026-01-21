<?php

    enum TokenTypes {
        case Lbrace;
        case Rbrace;
        case Lbracket;
        case Rbracket;
        case Colon;
        case Comma;
        case String;
        case Number;
        case True;
        case False;
        case Null;
    }

    class DKA {

        private $current_state = '-1';
        private $table_transition = [];
        private bool $isTerminal = false;
        private $buffer = '';

        public function __construct() {
            $this->table_transition = ['-1'=>["'"=>'1', "num"=>'3'], '1'=>["'"=>'2', "."=>'1'], '3'=>["."=>'4', "num"=>'3']];
        }

        public function check_automat($char) {
            if(is_numeric($char) || $char == "'") {
                return true;
            }
            else {
                return false;
            }
        }

        public function changeState($char) {
            $state = '';
            if(is_numeric($char)) {
                $state = 'num';
            }
            else if($char == "'") {
                $state = $char;
            }
            else {
                $state = '.';
            }
            $this->current_state = $this->table_transition[$this->current_state][$state];
            if($this->current_state == '2' || $this->current_state == '4') {
                $this->isTerminal = true;
                $this->current_state = '-1';
                return;
            }
            if ($this->current_state != '-1') {
                $this->buffer .= $char;
            }
        }

        public function getValue() {
            $val = $this->buffer;
            $this->buffer = '';
            return $val;
        }

        public function isTerminal(): bool {
            if($this->isTerminal) {
                $this->isTerminal = false;
                return true;
            }
            return false;
        }
    }

    class Token {
        public TokenTypes $type;
        public $value;

        public function __construct(TokenTypes $type, $value = null) {
            $this->type = $type;
            $this->value = $value;
        }
    }

    class Lexer {
        private $json;

        function __construct(string $str)
        {
            $this->json = $str;
        }

        function get_tokens() {
            $json = preg_replace("/\s+/", "", $this->json);
            $tokens = [];
            $len = strlen($json);
            $dka = new DKA();

            for ($i = 0; $i < $len; $i++) {
                $char = $json[$i];
                
                switch ($char) {
                    case '{': $tokens[] = TokenTypes::Lbrace; break;
                    case '}': $tokens[] = TokenTypes::Rbrace; break;
                    case '[': $tokens[] = TokenTypes::Lbracket; break;
                    case ']': $tokens[] = TokenTypes::Rbracket; break;
                    case ':': $tokens[] = TokenTypes::Colon; break;
                    case ',': $tokens[] = TokenTypes::Comma; break;
                    default: {
                        if ($dka->check_automat($char)) {
                            $dka->changeState($char);
                            if ($dka->isTerminal()) {
                                if ($char == "'") {
                                    $tokens[] = new Token(TokenTypes::String, $dka->getValue());
                                }
                                else {
                                    $tokens[] = new Token(TokenTypes::Number, $dka->getValue());
                                }
                            }
                        } elseif (preg_match('/[tfn]/', $char)) {
                            $value = '';
                            while ($i < $len && preg_match('/[a-z]/', $json[$i])) {
                                $value .= $json[$i];
                                $i++;
                            }
                            $i--;
                            switch ($value) {
                                case 'true': $tokens[] = TokenTypes::True; break;
                                case 'false': $tokens[] = TokenTypes::False; break;
                                case 'null': $tokens[] = TokenTypes::Null; break;
                            }
                        }
                        break;  
                    }
                }
            }
            return $tokens;
        }
    }

    $json = "{
        'a': 1,
        'b': '123',
        'c': true,
        'e': [1, 2, 3]
    }";
    $lexer = new Lexer($json);
    print_r($lexer->get_tokens());
?>