<?php

class MoulinetteCode {
    const NOT_EQUALS = 400;
    const NOT_FOUND = 404;
    const BAD_TYPE = 406;
    const ERROR_OCCURRED = 408;
}

class MoulinetteMessage {
    const OK = "OK";
    const NOT_EQUALS = "[method] : result expected for [params] is [expected]";
    const NOT_FOUND = "[method] function is not implemented";
    const BAD_TYPE = "[method] : An [type] must be return";
    const ERROR_OCCURRED = "An error occurred";
}

class MoulinetteException extends Exception {

    function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function printError() {
        $args = $this->getTrace()[0]["args"];
        $method = $args[0];
        $params = print_r($args[1], true);
        $expected = $args[2];
        switch ($this->getCode()) {
            case MoulinetteCode::NOT_FOUND:
                $message = str_replace("[method]", $method, $this->getMessage());
                break;
            case MoulinetteCode::NOT_EQUALS:
                if (gettype($expected) === "array") {
                    $expected = print_r($expected, true);
                }
                $message = str_replace(array("method", "[params]", "[expected]"), array($method, $params, $expected), $this->getMessage());
                break;
            case MoulinetteCode::BAD_TYPE:
                $message = str_replace(array("method", "[type]"), array($method, gettype($expected)), $this->getMessage());
                break;
            default:
                $message = $this->getMessage();
        }
        echo $message . PHP_EOL;
    }
}

class Moulinette {
    private array $testListByFunction;

    function __construct($testListByFunction) {
        $this->testListByFunction = $testListByFunction;
    }

    /**
     * @throws MoulinetteException
     */
    private function testing(string $function, array $params, $expected) {
        if (function_exists($function)) {
            try {
                $result = call_user_func_array($function, $params);
            } catch (Exception $e) {
                $message = MoulinetteMessage::ERROR_OCCURRED . !empty($e->getMessage()) ? " : " . $e->getMessage() : "";
                $code = $e->getCode() != 0 ? $e->getCode() : MoulinetteCode::ERROR_OCCURRED;
                throw new MoulinetteException($message, $code);
            }
            if ($result !== $expected) {
                if ($result == $expected) {
                    throw new MoulinetteException(MoulinetteMessage::BAD_TYPE, MoulinetteCode::BAD_TYPE);
                } else {
                    throw new MoulinetteException(MoulinetteMessage::NOT_EQUALS, MoulinetteCode::NOT_EQUALS);
                }
            }
        } else {
            throw new MoulinetteException(MoulinetteMessage::NOT_FOUND, MoulinetteCode::NOT_FOUND);
        }
    }

    private function calculatePercent(int $nbPassed): int {
        $percent = ($nbPassed / count($this->testListByFunction)) * 100;
        return number_format($percent,0);
    }

    private function getNoteMax(): int {
        $noteMax = 0;
        foreach ($this->testListByFunction as $testList) {
            $noteMax += $testList['note'];
        }
        return $noteMax;
    }

    public function exec() {
        $note = 0;
        $nbPassed = 0;
        foreach ($this->testListByFunction as $method => $testList) {
            $success = true;
            foreach ($testList['tests'] as $test) {
                try {
                    $this->testing($method, $test["params"], $test["expected"]);
                } catch (MoulinetteException $error) {
                    $error->printError();
                    $success = false;
                    break;
                }
            }
            if (!$success) {
                break;
            }
            $note += $testList["note"];
            $nbPassed++;
            echo "$method : " . MoulinetteMessage::OK . " => " . $testList['note'] . " point(s)" . PHP_EOL;
        }
        echo PHP_EOL . "$note/".$this->getNoteMax() . " (". $this->calculatePercent($nbPassed) . "%)" . PHP_EOL;
    }
}