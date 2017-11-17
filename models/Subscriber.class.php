<?php

class Person {
 /*   
    Id (int)
    Created (date/time)
    Deleted (boolean)
    Person (obj)
    -> DisplayName    +e
    -> NamePrefix     +e
    -> GivenName      +e
    -> FamilyName     +e
    -> NameSuffix     +e
    -> JobTitle       +e
    -> MobilePhone    +e
    -> WorkPhone      +e
    -> HomePhone      +e
    -> Fax            +e
    -> WebSiteAddress +e
    -> EmailAddress   +e
    -> RoleSet[]
    -> AddressSet[]
    -> StateCode
    -> PostalCode
    -> City
    -> AddressLine1
    -> AddressLine2
    -> State
    -> Country
    -> CountryCode
    -> EmployeeNumber +e
    -> Photo          +e
    -> LanguagePreference +e
    -> TimeZone		 +e
    InvitedBy
    IsGuest
*/
    protected $fields = array( 
  
    );
    
    function  __construct($personArr){
        foreach ($personArr as $key => $value){
           $this->$key = $value;
        }
    }
}