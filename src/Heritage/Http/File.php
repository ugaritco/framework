<?php

namespace Heritage\Http;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

class File extends SymfonyFile
{
    use FileHelpers;
}
