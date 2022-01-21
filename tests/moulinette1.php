<?php

require "src/Moulinette.php";

$test = array(
    "isPair" => array("note" => 1, "tests" => array(
        array(
            "params" => array(4),
            "expected" => true
        ),
        array(
            "params" => array(43),
            "expected" => false
        )
    )),
    "myStrlen" => array("note" => 2, "tests" => array(
        array(
            "params" => array("hello"),
            "expected" => 5),
        array(
            "params" => array("Let's go"),
            "expected" => 8),
        array(
            "params" => array(""),
            "expected" => 0),
    )),
    "findFirstCharacter" => array("note" => 3, "tests" => array(
        array(
            "params" => array("Bonjour je suis la Moulinette", "M"),
            "expected" => 19
        ),
        array(
            "params" => array("Je suis Un ordinateur", "U"),
            "expected" => 8
        ),
        array(
            "params" => array("pourquoi", "p"),
            "expected" => 0
        ),
        array(
            "params" => array("JE SUIS UN CAS ERREUR", "a"),
            "expected" => -1
        )
    )),
    "countArray" => array("note" => 2, "tests" => array(
        array(
            "params" => array(array("Hello", "World")),
            "expected" => 2
        ),
        array(
            "params" => array(array("Bonjo", "r, je s", "is ", "n ordinate", "r")),
            "expected" => 5
        )
    )),
    "replaceLetters" => array("note" => 3, "tests" => array(
        array(
            "params" => array("Bonjour les amis"),
            "expected" => "B0nj0ur l3s am1s"
        ),
        array(
            "params" => array("Les cours de programmation sont trops cools"),
            "expected" => "L3s c0urs d3 pr0grammat10n s0nt tr0ps c00ls"
        )
    )),
    "myExplode" => array("note" => 4, "tests" => array(
        array(
            "params" => array("Hello World", " "),
            "expected" => array("Hello", "World")
        ),
        array(
            "params" => array("Bonjour, je suis un ordinateur", "u"),
            "expected" => array("Bonjo", "r, je s", "is ", "n ordinate", "r")
        )
    )),
    "myImplode" => array("note" => 5, "tests" => array(
        array(
            "params" => array(array("Hello", "World"), " "),
            "expected" => "Hello World"
        ),
        array(
            "params" => array(array("Bonjo", "r, je s", "is ", "n ordinate", "r"), "u"),
            "expected" => "Bonjour, je suis un ordinateur"
        )
    ))
);
$moulinette = new Moulinette($test);
$moulinette->exec();