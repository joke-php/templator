<?php

namespace Vasoft\Joke\Templator\Container;

enum DirectiveType: int
{
    case UNKNOWN = 0;
    case BEGIN = 1;
    case END = 2;
    case SINGLE = 3;
    case BRANCH = 4;
}