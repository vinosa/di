<?php

/*
 * The MIT License
 *
 * Copyright 2018 vinogradov.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Vinosa\Di ;
/**
 * Description of Container
 *
 * @author vinosa
 */
class Container implements \Psr\Container\ContainerInterface
{
    protected $entries = [];
    protected $typedParameters = [] ;
    protected $untypedParameters = [] ;          
    /**
     * gets an existing entry, if not tries lazy loading
     * @param type $id
     * @return type
     * @throws NotFoundException
     */          
    public function get($id)
    {
        if( $this->has( $id ) ){            
            return $this->entries[$id] ;            
        }        
        if(class_exists($id)){            
            return $this->newInstance( new \ReflectionClass( $id ) ) ;
        }               
        throw new NotFoundException("no entry : ". $id ) ;
    }
    
    public function has($id): bool
    {
        return isset( $this->entries[$id] ) ;
    }
    
    public function bind($class, $value)
    {       
        $this->entries[$class] = $value;        
    }
    
    public function bindParameterByType(string $type, string $declaringClass, $value)
    {
        $this->typedParameters[$type . $declaringClass] = $value ;
    }
    
    public function bindParameterByName(string $name, string $declaringClass, $value)
    {
        $this->untypedParameters[$name . $declaringClass] = $value ;
    }
      
    protected function resolveParameter(\ReflectionParameter $parameter)
    { 
        if($this->has( $parameter->getName() . $parameter->getDeclaringClass()->getName()) ){
            return $this->get( $parameter->getName() . $parameter->getDeclaringClass()->getName()) ;
        }
        if( !is_null( $parameter->getClass() ) ){          
            if($this->has( $parameter->getClass()->getName() . $parameter->getDeclaringClass()->getName() ) ){
                return $this->get( $parameter->getClass()->getName() . $parameter->getDeclaringClass()->getName() ) ;
            }
            if($this->has( $parameter->getClass()->getName() ) ){
                return $this->get( $parameter->getClass()->getName() );
            }
            return $this->newInstance( $parameter->getClass() ) ;         
        } 
        try{            
            return $parameter->getDefaultValue();            
        } catch (\ReflectionException $ex) {
        }        
        return null ;                 
    }
    
    protected function newInstance(\ReflectionClass $reflectionClass)
    {
        if( !is_null( $reflectionClass->getConstructor() ) ){
            $new = $this->newInstanceWithConstructor( $reflectionClass);           
        }
        else{            
            $new = $reflectionClass->newInstance();
        }                                             
        return $new ;
    }
    
    protected function newInstanceWithConstructor(\ReflectionClass $reflectionClass)
    {             
        $parameters = [] ;        
        foreach($reflectionClass->getConstructor()->getParameters() as $parameter){                                           
            $parameters[] = $this->resolveParameter($parameter);                               
        }                             
        return $reflectionClass->newInstanceArgs($parameters) ;
    }
        
}
class NotFoundException extends \Exception implements \Psr\Container\NotFoundExceptionInterface
{
    
}
