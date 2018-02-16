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
    
    protected $entries = [];
    protected $interfaces = [];
    protected $declaringClassParameters = [];
    protected $typedParameters = [] ;
    protected $untypedParameters = [] ;
          
    public function get($id)
    {
        if( $this->has( $id ) ){
            
            return $this->entries[$id] ;
            
        }
               
        throw new ContainerException("no entry : ". $id ) ;
    }
    
    public function has($id)
    {
        return isset( $this->entries[$id] ) ;
    }
    
    public function resolve($class, \ReflectionClass $declaringClass = null )
    {
        
        $class = $this->getInterface( $class, $declaringClass ) ; // in case we bound this class or interface to another
                     
        if( $this->has( $class ) ){
            
            return $this->get( $class ) ; // class was bound to a value
        }
        
        return $this->newInstance( new \ReflectionClass( $class ), $declaringClass ) ;
    }
    
    
    public function bind($class, $value)
    {       
        $this->entries[$class] = $value;
        
    }
    
    public function bindParameterByType($parameterClass, $declaringClass, $value)
    {
        $this->typedParameters[$parameterClass . $declaringClass] = $value ;
    }
    
    public function bindParameterByName($parameterName, $declaringClass, $value)
    {
        $this->untypedParameters[$parameterName . $declaringClass] = $value ;
    }
    
    public function bindInterface($interface , $class , $declaringClass = "" )
    {
        
        $this->interfaces[ $interface . $declaringClass ] = $class ;
        
    }
    
    
    public function resolveTypedParameter( \ReflectionParameter $parameter )
    {
        
        $value = $this->findBoundParameter($parameter, $parameter->getDeclaringClass() );
        
        if($value != false){
            
            return $value ;
        }
                                 
        return $this->resolve( $parameter->getClass()->getName() ,  $parameter->getDeclaringClass() );
    }
                
    public function resolveUntypedParameter(\ReflectionParameter $parameter, \ReflectionClass $declaringClass = null )
    {
        if( !is_null($declaringClass) && $this->boundToDeclaringClass( $parameter ) ){
            
            return $declaringClass->getName() ; //  if the parameter value was bound to parent declaring class
        }
                     
        $value = $this->findBoundParameter($parameter, $parameter->getDeclaringClass() );
       
        if($value != false){
            
            return $value ;
        }
        
        try{
            
            return $parameter->getDefaultValue();
            
        } catch (\ReflectionException $ex) {

        }
        
        return null ;
        
    }
    
    public function getInterface( $interface, \ReflectionClass $declaringClass = null )
    {
                
        if(!is_null($declaringClass) && isset($this->interfaces[$interface . $declaringClass->getName() ] ) ){
            
            return $this->interfaces[ $interface . $declaringClass->getName() ] ;   // if bind(interface, class, declaringClass) 
                
        }
        
        if(isset($this->interfaces[$interface])){
            
            return $this->interfaces[$interface] ;                                  // if bind(interface, class) 
                
        }
                    
        return $interface ; // return the same class
        
    }
        
    
    protected function resolveParameter(\ReflectionParameter $parameter, \ReflectionClass $reflectionDeclaringClass = null)
    {
                                                                     
        if( !is_null( $parameter->getClass() ) ){                                               // if the parameter is a Class or an Interface
                              
            return $this->resolveTypedParameter( $parameter ) ;
           
        }
        
        return $this->resolveUntypedParameter($parameter, $reflectionDeclaringClass) ;
                      
    }
    
    protected function newInstance(\ReflectionClass $reflectionClass, \ReflectionClass $declaringClass = null)
    {
        
        if( !is_null( $reflectionClass->getConstructor() ) ){

            $new = $this->newInstanceWithConstructor( $reflectionClass, $declaringClass );
            
        }
        else{
            
            $new = $reflectionClass->newInstance();
        }
                     
        if( $reflectionClass->hasMethod("setContainer") ){
             
            $new->setContainer( $this );
        }
                             
        return $new ;
    }
    
    protected function newInstanceWithConstructor(\ReflectionClass $reflectionClass, \ReflectionClass $declaringClass = null)
    {
              
        $parameters = [] ;
        
        foreach($reflectionClass->getConstructor()->getParameters() as $parameter){                
                            
            $parameters[] = $this->resolveParameter($parameter , $declaringClass );
                               
        }              
               
        return $reflectionClass->newInstanceArgs( $parameters ) ;
    }
     
    
    public function bindToDeclaringClass( $parameter, $declaringClass)
    {
        $this->declaringClassParameters[$declaringClass . $parameter] = true ;
    }
    
    protected function boundToDeclaringClass(\ReflectionParameter $parameter)
    {
        
        if(isset($this->declaringClassParameters[ $parameter->getDeclaringClass()->getName() . $parameter->getName() ] ) ){
            
            return true;
            
        }

        return false;
    }
    
    protected function findBoundParameter( \ReflectionParameter $parameter, \ReflectionClass $class )
    {
        $key = $parameter->getName() . $class->getName() ; // bound by name ?
        
        if( isset($this->untypedParameters[$key]) ){
            
            return $this->untypedParameters[$key] ;
        }
        
        if($parameter->getClass() != false){
            
            $key = $parameter->getClass()->getName() . $class->getName() ;   // bound by class/interface ?
             
            if( isset($this->typedParameters[$key]) ){
            
                return $this->typedParameters[$key] ;
            }
             
        }
                 
        if( !$class->getParentClass() ){
            
            return false ; // all parent classes have been looked and no one has the parameter bound
        }
        
        return $this->findBoundParameter($parameter, $class->getParentClass() ) ; // move to the parent
    }
    
    
}
