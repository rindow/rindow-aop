<?php
namespace Rindow\Aop\Support\Pointcut;

class Lexer
{
    protected $location;
    protected $doc;
    protected $pos=-1;
    protected $next=0;
    protected $marks=-1;

    public function __construct($doc,$location)
    {
        $this->location = $location;
        $this->doc = trim($doc," \n\t");
    }

    public function get($skipSpace=true)
    {
        $retry = 5;
        if($this->pos == -1)
            $this->next();
        while (true) {
            $word = substr($this->doc, $this->pos, $this->next - $this->pos);
            if(empty($word))
                return false;
            if(!$skipSpace)
                return $word;
            $word = trim($word," \n\t");
            if(!empty($word))
                return $word;
            $this->next();
        }
    }

    public function next()
    {
        $this->pos = $this->next;
        if($this->marks>0) {
            $this->next += $this->marks;
            $this->marks=-1;
            return;
        }
        while (($c=substr($this->doc, $this->next, 1))!==false) {
            switch ($c) {
                case '(':
                case ')':
                case '!':
                    if($this->marks) {
                        $this->next +=1;
                        $this->marks =-1;
                    } else {
                        $this->marks = 1;
                    }
                    return;

                case '&':
                case '|':
                    $marks = 1;
                    if(substr($this->doc, $this->next, 1)==$c) {
                        $marks += 1;
                    }
                    if($this->marks) {
                        $this->next +=$marks;
                        $this->marks =-1;
                    } else {
                        $this->marks =$marks;
                    }
                    return;
            }
            $this->marks =0;
            $this->next += 1;
        }
    }

    public function where()
    {
        return $this->location;
    }
}