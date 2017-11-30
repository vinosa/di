<?php

/*
 * Copyright 2017 vinogradov.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Vinosa\Di ;
/**
 * Description of Container
 *
 * @author vinosa
 */
class Container
{
    protected $entries = array();
    protected $interfaces = array();
          
    public function get($id)
    {
        if( $this->has( $id ) ){
            
            return $this->entries[$id] ;
            
        }
               
        throw new ContainerException("not found : ". $id ) ;
    }
    
    public function has($id)
    {
        return isset( $this->entries[$id] ) ;
    }
    
    
    public function bind($class, $declaringClass = "",  $value)
    {
        $id = $class  . $declaringClass;
        
        $this->entries[$id] = $value;
    }
    
    public function resolve($class, $declaringClass = "" )
    {
        $id = $class  . $declaringClass;
        
        if( $this->has( $id ) ){
            
            return $this->get( $id ) ;
        }
        
        $reflectionClass = new \ReflectionClass( $class );
                
        $new = $this->newInstance( $reflectionClass ) ;
                          
        $this->bind($class, $declaringClass, $new);
         
        return $new ;
    }
    
    
    protected function resolveParameter(\ReflectionParameter $parameter)
    {
               
        $class = $parameter->getClass();
        
        $declaringClass = $parameter->getDeclaringClass()->getName() ;
        
        if( !is_null( $class ) ){
            
            $className = $class->getName() ;
                
            if($this->getInterface($className) != false){
                
                $className = $this->getInterface($className) ;
                
            }
                               
            return $this->resolve( $className, $declaringClass ) ;
           
        }
        
        if( $this->has($declaringClass . $parameter->getName() ) ){
            
            return $this->get($declaringClass . $parameter->getName() ) ;
            
        }
        
        try{
            
            return $parameter->getDefaultValue();
            
        } catch (\ReflectionException $ex) {

        }
        
        return null ;
        
    }
    
    protected function newInstance(\ReflectionClass $reflectionClass)
    {
        $class = $reflectionClass->getName();
        
        $constructor = $reflectionClass->getConstructor();
        
        if(!is_null($constructor)){

            return $this->newInstanceWithConstructor( $reflectionClass );
            
        }
        
        $new = new $class ;
             
        if($reflectionClass->hasMethod("setContainer") ){
             
            $new->setContainer( $this );
        }
                             
        return $new ;
    }
    
    protected function newInstanceWithConstructor(\ReflectionClass $reflectionClass)
    {
        
        $constructor = $reflectionClass->getConstructor();
        
        $reflectionParameters = $constructor->getParameters(); 
         
        $class = $reflectionClass->getName();
        
        $parameters = array();
        
        foreach($reflectionParameters as $parameter){                
                            
            $parameters[] = $this->resolveParameter($parameter);
                               
        }              

         $new = $reflectionClass->newInstanceArgs( $parameters );
         
         if($reflectionClass->hasMethod("setContainer") ){
             
             $new->setContainer( $this );
         }
                
         return $new ;
    }
    
    public function bindInterface($interface , $class)
    {
        $this->interfaces[ $interface ] = $class ;
    }
    
    public function getInterface($interface, $defaultValue = false)
    {
        if(isset($this->interfaces[$interface])){
            
            return $this->interfaces[$interface] ;
                
        }
        
        return $defaultValue ;
    }
}
