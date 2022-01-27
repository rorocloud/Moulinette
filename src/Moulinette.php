<?php

class MoulinetteCode {
    const NOT_EQUALS = 400;
    const NOT_FOUND = 404;
    const BAD_TYPE = 406;
    const ERROR_OCCURRED = 408;
}

class MoulinetteMessage {
    const OK = "OK";
    const NOT_EQUALS = "Result expected for [method]([params]) is [expected] and your result is [result]";
    const NOT_FOUND = "[method] function is not implemented";
    const BAD_TYPE = "[method] : An [expectedType] must be return and your result is an [resultType]";
    const ERROR_OCCURRED = "An error occurred";
}

class MoulinetteException extends Exception {
    private array $details;
    private bool $isFormatDetails = false;

    function __construct(string $message = MoulinetteMessage::ERROR_OCCURRED, int $code = MoulinetteCode::ERROR_OCCURRED, array $details = array(), Throwable $previous = null) {
        if (!empty($details)) {
            $this->formatDetails($details);
        } else if ($message != MoulinetteMessage::ERROR_OCCURRED) {
            $message = MoulinetteMessage::ERROR_OCCURRED;
            $code = MoulinetteCode::ERROR_OCCURRED;
        }
        parent::__construct($message, $code, $previous);
    }

    private function setTypeAndValue($value): array {
        return array("type" => gettype($value), "value" => $value);
    }

    private function formatDetails(array $details) {
        $this->details["method"] = $details["method"] ?? "";
        if (isset($details["params"])) {
            foreach ($details["params"] as $value) {
                $this->details["params"][] = $this->setTypeAndValue($value);
            }
        }
        $this->details["expected"] = isset($details["expected"]) ? $this->setTypeAndValue($details["expected"]) : null;
        $this->details["result"] = isset($details["result"]) ? $this->setTypeAndValue($details["result"]) : null;
        if (isset($this->details["method"]) && isset($this->details["params"]) && isset($this->details["expected"]) && isset($this->details["result"])) {
            $this->isFormatDetails = true;
        }
    }

    private function printValue(string $type, $value, bool $printType = false): string {
        $toPrint = "";
        if ($type === "array") {
            $toPrint .= $this->printArray($value, $printType);
        } else if ($type === "string") {
            $toPrint .= "\"$value\"" . ($printType ? " ($type)" : "");
        } else {
            $toPrint .= "$value" . ($printType ? " ($type)" : "");
        }
        return $toPrint;
    }

    private function printArray(array $array, bool $printType = false): string {
        $strToPrint = "array(";
        $index = 0;
        $nbItem = count($array);
        foreach ($array as $item) {
            $type = gettype($item);
            if ($type === "array") {
                $strToPrint .= $this->printArray($item, $printType);
            } else {
                $strToPrint .= $this->printValue($type, $item, $printType);
            }
            $index++;
            if ($index < $nbItem) {
                $strToPrint .= ", ";
            }
        }
        $strToPrint .= ")";
        return $strToPrint;
    }

    private function printParamValue(array $params, bool  $printType = false): string {
        $strToPrint = "";
        $index = 0;
        $nbItem = count($params);
        foreach ($params as $item) {
            $type = $item["type"];
            $value = $item["value"];
            if ($type === "array") {
                $strToPrint .= $this->printArray($value, $printType);
            } else {
                $strToPrint .= $this->printValue($type, $value, $printType);
            }
            $index++;
            if ($index < $nbItem) {
                $strToPrint .= ", ";
            }
        }
        return $strToPrint;
    }

    public function printError() {
        $message = MoulinetteMessage::ERROR_OCCURRED;
        if ($this->isFormatDetails) {
            switch ($this->getCode()) {
                case MoulinetteCode::NOT_FOUND:
                    $message = str_replace("[method]", $this->details["method"], $this->getMessage());
                    break;
                case MoulinetteCode::NOT_EQUALS:
                    $params = $this->printParamValue($this->details['params']);
                    $expectedType = $this->details['expected']['type'];
                    $expected = $this->printValue($expectedType, $this->details['expected']['value']);
                    $resultType = $this->details['result']['type'];
                    $result = $this->printValue($resultType, $this->details['result']['value']);
                    $message = str_replace(array("[method]", "[params]", "[expected]", "[result]"),
                                array($this->details["method"], $params, $expected, $result), $this->getMessage());
                    break;
                case MoulinetteCode::BAD_TYPE:
                    $message = str_replace(array("method", "[expectedType]", "[resultType]"), array($this->details["method"], $this->details["expected"]["type"], $this->details["result"]["type"]), $this->getMessage());
                    break;
                default:
                    $message = $this->getMessage();
            }
        }
        echo PHP_EOL . $message . PHP_EOL;
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
        $details = array("method" => $function, "params" => $params, "expected" => $expected);
        if (function_exists($function)) {
            try {
                $result = call_user_func_array($function, $params);
                $details["result"] = $result;
            } catch (Exception $e) {
                $message = MoulinetteMessage::ERROR_OCCURRED . !empty($e->getMessage()) ? " : " . $e->getMessage() : "";
                $code = $e->getCode() != 0 ? $e->getCode() : MoulinetteCode::ERROR_OCCURRED;
                throw new MoulinetteException($message, $code, $details);
            }
            if ($result !== $expected) {
                if ($result == $expected) {
                    throw new MoulinetteException(MoulinetteMessage::BAD_TYPE, MoulinetteCode::BAD_TYPE, $details);
                } else {
                    throw new MoulinetteException(MoulinetteMessage::NOT_EQUALS, MoulinetteCode::NOT_EQUALS, $details);
                }
            }
        } else {
            throw new MoulinetteException(MoulinetteMessage::NOT_FOUND, MoulinetteCode::NOT_FOUND, $details);
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