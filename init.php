<?php

/**
 * @package Packer v3.1 (beta?), PHP port
 * @author Dean Edwards, fixed by Nao (Wedge.org), fixed by tholu
 * @license http://www.opensource.org/licenses/mit-license.php
 * @from http://code.google.com/p/base2/source/browse/trunk/src/apps/packer#packer%2Fphp
 */

Parser::$dictionary = new RegGrp(Parser::$dictionary);

// Initialize static object properties
Packer::$data = new Parser(Packer::$data);

// Initialize static object properties
Minifier::$clean = Packer::$data->union(new Parser(Minifier::$clean));
Minifier::$concat = new Parser(Minifier::$concat);
Minifier::$concat->merge(Packer::$data);
Minifier::$comments = Packer::$data->union(new Parser(Minifier::$comments));
Minifier::$conditionalComments = Minifier::$comments->copy();
Minifier::$conditionalComments->putAt(-1, ' $3');
Minifier::$whitespace = Packer::$data->union(new Parser(Minifier::$whitespace));
Minifier::$whitespace->removeAt(2); // Conditional comments
Minifier::$comments->removeAt(2);


