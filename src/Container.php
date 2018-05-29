<?php

/*
 * Copyright 2017 vinosa.
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
class Container implements \Psr\Container\ContainerInterface
{
    
    protected $entries = [];
    protected $interfaces = [];
    protected $declaringClassParameters = [];
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
            
            return $this->resolve($id) ;
        }
               
        throw new NotFoundException("no entry : ". $id ) ;
    }
    
    public function has($id)
    {
        return isset( $this->entries[$id] ) ;
    }
    
    /**
     * lazy loads class
     * @param type $class
     * @param \ReflectionClass $declaringClass
     * @return type
     */
    
    public function resolve($class, \ReflectionClass $declaringClass = null )
    {
        
        if( $this->has( $class ) ){
            
            return $this->get( $class ) ; // class was bound to a value
        }
        
        return $this->newInstance( new \ReflectionClass( $class ), $declaringClass ) ;
    }
    
    
    public function bind($class, $value)
    {       
        $this->entries[$class] = $value;
        
    }
    /**
     * 
     * @param type $declaringClass Declaring class
     * @param type $parameterClass Parameter class or interface
     * @param type $value Parameter value
     */
    public function bindConstructorParameterByType($declaringClass, $parameterClass, $value)
    {
        $this->typedParameters[$parameterClass . $declaringClass] = $value ;
    }
    /**
     * 
     * @param type $declaringClass Declaring class
     * @param type $parameterName Parameter name
     * @param type $value Parameter value
     */
    public function bindConstructorParameterByName($declaringClass, $parameterName, $value)
    {
        $this->untypedParameters[$parameterName . $declaringClass] = $value ;
    }
    
    
    protected function resolveTypedParameter( \ReflectionParameter $parameter )
    {
        
        $value = $this->findBoundParameter($parameter, $parameter->getDeclaringClass() );
        
        if($value != false){
            
            return $value ;
        }
                                 
        return $this->resolve( $parameter->getClass()->getName() ,  $parameter->getDeclaringClass() );
    }
                
    protected function resolveUntypedParameter(\ReflectionParameter $parameter, \ReflectionClass $declaringClass = null )
    {
                
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
class NotFoundException extends \Exception implements \Psr\Container\NotFoundExceptionInterface
{
    
}
