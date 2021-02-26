<?php
namespace EasyUtils\Kernel\tests;
use EasyUtils\book\Service\BookFacade;
use EasyUtils\Kernel\Support\Image;
use PHPUnit\Framework\TestCase;


class ImageTest extends TestCase
{
    public function testResize()
    {
        $res = Image::resize('C:\Users\my\Desktop\avatar2.jpg', 128, 'C:\Users\my\Desktop\avatar22.jpg');
        v($res);
    }
}