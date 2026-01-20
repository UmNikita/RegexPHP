<?php

    enum StateRegex {
        case Valid;
        case Invalid;
    }

    class RegexException extends Exception {}

    class Regex {

        private StateRegex $state = StateRegex::Invalid;
        private $valid_limiters = ['/', '#', '~', '%', '!', '|'];
        private Machine $machine;

        public function __construct(string $expression) {
            $this->state = $this->check_str_valid($expression);
            if($this->state == StateRegex::Valid) {
                $str = $this->str_extract($expression);
                $this->machine = new Machine();
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

    class Machine {

        private $current_states = [0];
        private $states = [];
        private $states_is_qvants = [];
        private $table_transition = [];
        private $alphobet = [];
        private bool $isTerminal = false;

        public function makeMachine($template) {
            $tokens = [];
            for ($i = 0; $i < strlen($template); $i++) {
                if($template[$i] == '[') {
                    $count = 0;
                    for ($j = $i; $j < strlen($template); $j++) {
                        if($template[$j] == ']') {
                            break;
                        }
                        $count++;
                        $token .= $template[$j];
                    }
                    $i += $count;
                    $token .= ']';
                    $tokens[] = $token;
                    continue;
                }
                else {
                    if($template[$i+1] == '*') {
                        $tokens[] = $template[$i].'*';
                        $i++;
                    }
                    else if($template[$i+1] == '?') {
                        $tokens[] = $template[$i].'?';
                        $i++;
                    }
                    else
                        $tokens[] = $template[$i];
                }
            }

            //alphobet
            for ($i = 0; $i < strlen($template); $i++) {
                if($template[$i] == '[' || $template[$i] == ']' || $template[$i] == '.' || $template[$i] == '*' || $template[$i] == '?') {
                    continue;
                }
                if($template[$i] == '-') {
                    $i++;
                    $digits = range($template[$i-2], $template[$i]);
                    for ($j = 0; $j < count($digits); $j++) {
                        if(!in_array($digits[$j], $this->alphobet)) {
                            $this->alphobet[] = $digits[$j];
                        }
                    }
                    continue;
                }
                if(!in_array($template[$i], $this->alphobet)) {
                    $this->alphobet[] = $template[$i];
                }
            }

            //set states
            for ($i = 0; $i < count($tokens); $i++) {
                $state = "";
                if($i == 0) {
                    $state = $tokens[0];
                }
                else {
                    $state = $this->states[$i-1].$tokens[$i];
                }
                $this->states[] = $state;
                if($state[strlen($state)-1] == '*' || $state[strlen($state)-1] == '?') {
                    $this->states_is_qvants[$state] = true;
                }
                else {
                    $this->states_is_qvants[$state] = false;
                }
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
                if($symbol[strlen($symbol)-1] == ']') {
                    $j = strlen($symbol)-2;
                    while ($symbol[$j] != '[') {
                        if($symbol[$j] == '-') {
                            $digits = range($symbol[$j-1], $symbol[$j+1]);
                            for ($k = 0; $k < count($digits)-1; $k++) {
                                $this->table_transition[$key][$digits[$k]] = $pointer;
                            }
                            $j--;
                        }
                        else {
                            $this->table_transition[$key][$symbol[$j]] = $pointer;
                            $j--;
                        }
                        
                    }
                    continue;
                }
                else if($symbol[strlen($symbol)-1] == '*' || $symbol[strlen($symbol)-1] == '?') {
                    $this->table_transition[$key]['eps'] = $pointer;
                    $this->table_transition[$key][$symbol[strlen($symbol)-2]] = $pointer;
                    continue;
                }
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
        }

        public function changeState($char) {
            for ($i = 0; $i < count($this->current_states); $i++) {
                $state = '';
                if($this->current_states[$i] == 0) {
                    $state = '-1';
                }
                else {
                    $state = $this->states[$this->current_states[$i]-1];
                }
                if(!in_array($char, $this->alphobet))
                    $this->current_states[$i] = $this->table_transition[$state]['.'];
                else
                    $this->current_states[$i] = $this->table_transition[$state][$char];
                if($this->states_is_qvants[$this->states[$this->current_states[$i]-1]]) {
                    $this->current_states[] = $this->table_transition[$state]['eps'];
                }
                if($this->current_states[$i] == count($this->states)) {
                    $this->isTerminal = true;
                    for ($i = 0; $i < count($this->current_states); $i++) {
                        $this->current_states[$i] = 0;
                    }
                    return;
                }
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
    
    // $txt = "adwzaqd";
    // $regex = new Regex("/ab/");
    // if($regex->test($txt)) {
    //     echo 1;
    // }
    // else {
    //     echo 0;
    // }
?>