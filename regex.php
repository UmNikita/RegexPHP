<?php

    enum StateRegex {
        case Valid;
        case Invalid;
    }

    class RegexException extends Exception {}

    class Regex {

        private StateRegex $state = StateRegex::Invalid;
        private $valid_limiters = ['/', '#', '~', '%', '!', '|'];
        private $expression = "";
        private DKA $machine;

        public function __construct(string $expression = null) {
            $this->state = $this->check_str_valid($expression);
            $this->expression = $expression;
            if($this->state == StateRegex::Valid) {
                $str = $this->str_extract($expression);
                $this->machine = new DKA();
                $this->machine->makeMachine($str);
            }
        }

        private function check_str_valid($expression): StateRegex {
            if (empty($expression)) {
                return StateRegex::Invalid;
            }
            $state = StateRegex::Valid;
            $symbol_lim = $expression[0];
            $isFinishLimSymbol = false;
            for ($i = 0; $i < strlen($expression); $i++) {
                if($i == 0) {
                    $isValidLim = false;
                    foreach ($this->valid_limiters as $name) {
                        if($symbol_lim == $name) {
                            $isValidLim = true;
                            break;
                        }
                    }
                    if($isValidLim == false) {
                        $state = StateRegex::Invalid;
                        break;
                    }
                }
                else {
                    if($expression[$i] == $symbol_lim) {
                        $isFinishLimSymbol = true;
                    } 
                }
                
            }
            if($isFinishLimSymbol == false) {
                $state = StateRegex::Invalid;
            }
            return $state;
        }

        private function str_extract($str): string {
            $symbol = $str[0];
            $extracted_str = "";
            for ($i = 1; $i < strlen($str); $i++) {
                if($str[$i] == $symbol) {
                    return $extracted_str;
                }
                else {
                    $extracted_str .= $str[$i];
                }
            }
            return $extracted_str;
        }

        public function isValid(): bool {
            if ($this->state == StateRegex::Valid) {
                return true;
            }
            else {
                return false;
            }
        }

        public function test($str): bool {
            if($this->state == StateRegex::Invalid) {
                throw new RegexException("Невалидное выражение", 1);
                return false;
            }
            for ($i = 0; $i < strlen($str); $i++) {
                $this->machine->changeState($str[$i]);
                if($this->machine->isTerminal()) {
                    return true;
                }
            }
           return false;
        }
    }

    class DKA {

        private $current_state = 0;
        private $states = [];
        private $table_transition = [];
        private $alphobet = [];
        private bool $isTerminal = false;

        public function makeMachine($template) {
            //alphobet
            for ($i = 0; $i < strlen($template); $i++) {
                $isUniq = true;
                for ($j = 0; $j < count($this->alphobet); $j++) {
                    if($this->alphobet[$j] == $template[$i]) {
                        $isUniq = false;
                        break;
                    }
                }
                if($isUniq && $template[$i] != '.') {
                    $this->alphobet[] = $template[$i];
                }
            }

            //set states
            for ($i = 0; $i < strlen($template); $i++) {
                if($i == 0) {
                    $this->states[] = $template[0];
                    continue;
                }
                $this->states[] = $this->states[$i-1].$template[$i];
            }

            //set table transiton
            //luck
            $alphobet_keys = [];
            for ($i = 0; $i < count($this->alphobet); $i++) {
                $alphobet_keys[$this->alphobet[$i]] = '0';
            }
            $alphobet_keys['.'] = '0';
            $this->table_transition['-1'] = $alphobet_keys;
            for ($i = 0; $i < count($this->states)-1; $i++) {
                $key = $this->states[$i];
                $this->table_transition[$key] = $alphobet_keys;
            }
            $pointer = 0;
            foreach ($this->table_transition as $key => $value) {
                $symbol = $this->states[$pointer];
                $pointer++;
                $this->table_transition[$key][$symbol[strlen($symbol)-1]] = $pointer;
            }

            //unluck
            foreach ($this->table_transition as $state => &$row) {
                foreach ($row as $char => $to) {
                    if ($to != '0') {
                        continue;
                    }
                    if ($state === '-1') {
                        $row[$char] = 0;
                        continue;
                    }
                    $candidate = $state . $char;
                    $next = 0;
                    for ($len = strlen($candidate); $len > 0; $len--) {
                        $suffix = substr($candidate, -$len);
                        foreach ($this->states as $i => $prefix) {
                            if ($prefix === $suffix) {
                                $next = $i + 1;
                                break 2;
                            }
                        }
                    }
                    $row[$char] = $next;
                }
            }
            unset($row);
            $state = "";
            for ($i = 0; $i < strlen($template); $i++) {
                if($template[$i] == '.') {
                    foreach ($this->table_transition[$state] as $key => $value) {
                        $this->table_transition[$state][$key] = $i+1;
                    }
                }
                $state .= $template[$i];
            }
            //print_r($this->table_transition);
        }

        public function changeState($char) {
            $state = '';
            if($this->current_state == 0) {
                $state = '-1';
            }
            else {
                $state = $this->states[$this->current_state-1];
            }
            if(!in_array($char, $this->alphobet))
                $this->current_state = $this->table_transition[$state]['.'];
            else
                $this->current_state = $this->table_transition[$state][$char];
            if($this->current_state == count($this->states)) {
                $this->isTerminal = true;
                $this->current_state = 0;
            }
        }

        public function isTerminal(): bool {
            if($this->isTerminal) {
                $this->isTerminal = false;
                return true;
            }
            return false;
        }
    }
    
    $txt = "adaqdd";
    $regex = new Regex("/a[q]d/");
    if($regex->test($txt)) {
        echo 1;
    }
    else {
        echo 0;
    }
?>