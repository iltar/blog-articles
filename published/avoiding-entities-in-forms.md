[//]: # (TITLE: Avoiding Entities in Forms)
[//]: # (DATE: 2016-05-20T13:46:00+01:00)
[//]: # (TAGS: php, doctrine, entity, form, data transfer object)

Being active on IRC, almost every day I see questions coming by regarding forms and entities inside. This does not only give you a headache but it's also risky. You wouldn't want to flush an entity in an invalid state!

## But Using Entities in my Forms is Easy!
Yes, it's certainly easy. You don't have to write any additional code to connect your validation rules and data mapping, not to mention that when it's valid, you only have to flush your entity and you're done. Using this method is especially easy when using CRUD and makes developing applications faster, thus RAD friendly.

## Then what is the issue?
Where should I start? 
 - Entities should always be in a valid state
 - Entities require additional guessing logic when filling in default data
 - Entities limit the data structure and re-usability of your form types
 
### What do you mean with a valid state?
This means that from the moment it's created, it should comply with your business rules. If your Authentication`s username is mandatory, it should always be present and thus means you have to add it to the constructor. This is usually not the only requirement though, another rule might be that it has to be at least 5 characters long.

```php
<?php
// Symfony forms do not support this by default, you have to configure this
$user = new Authentication($username);

// snippet of our class
public function setUsername($username)
{
    if (strlen($username) < 5) {
        throw new \InvalidArgumentException('The username should be at least 5 characters.')
    }
    
    $this->username = $username;
}
```
Like this you prevent your Authentication from reaching an invalid state, but the form component does not really like this. Internally the form component tries to call `setUsername` with the mapped path in your form.

```php
<?php
// maps to the username property and will call getUsername and setUsername
$builder->add('username', TextType::class);
```
Now the user wants to change his or her username and fills in "Foo", this will trigger the Exception when the property accessor tries to call `setUsername('Foo')`. This effectively means you cannot make sure entities remain in a valid state as the exception would have to be removed in order to have your Authentication be suitable as data object.

If you were to remove the exception and allow the entity to be in an invalid state, any `flush()` (no args) will save this entity to your database in this state.

### What do you mean with additional guessing logic?
I'm mainly referring to the [EntityType][entity-type] in Symfony forms. This requires you to tightly couple your EntityRepository to your form. I won't go into depth on this one as I have never found a reason to use it before.

### What do you mean with limiting the data structure and re-usability?
When your entity is your data object, you're limited to the structure of your entity when mapping your type. This means that if you want to grab a part that's commonly shared between forms, you cannot simply re-use sub-types as this would alter the data structure required. Another solution would be to add "temporary" properties in your entity which are only used in the form. This would break the Single Responsibility Principle though.

You can get pretty far with validation groups, but this requires you to configure your validation groups for every single form using this entity. When creating a new user, you want a unique validation on your username, but not when modifying as it will stay the same.

How would you add a `newUsername` to your entity? As there is no `newUsername` property, you can't do it like this:
```php
<?php
// lacking the properties and re-usability
$builder->add('newUsername', RepeatedType::class, [/* options */]);
```

## So what would be the solution?
[Data Transfer Objects - DTO][dto-martin-fowler]. Simply put, a  Plain Old PHP Object - POPO to contain your data. It requires you to write an additional class for your form, but will be worth it on the long run. If you take the examples from before, you can easily solve that with the following snippets:

*Please note that this is not a working example*

### The DTO
```php
<?php
// can also contain privates with getters/setters if you like
class ChangeUsernameData
{
    /** 
     * @Some\Custom\Assert\Unique(entity="App\Entity\Authentication", field="username")
     * @Assert\Length(5, 16)
     **/
    public $newUsername;
}
```
### Creating the type
```php
<?php
// no problems now because you can map all fields accordingly
$builder->add('newUsername', RepeatedType::class, [/* options */]);
```
### Creating and handling the form
```php
<?php
public function changeUsernameAction(Request $request, Authentication $authentication)
{
    $changeUsername = new ChangeUsernameData();
    $form = $this->formFactory->create(ChangeUsernameType::class, $changeUsername);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $authentication->setUsername($changeUsername->newUsername);
        $this->em->flush($authentication);
        
        // redirect
    }
    
    // render form
}
```

Congratulations, you have now made your forms unaware of entities!

[entity-type]:http://symfony.com/doc/current/reference/forms/types/entity.html
[dto-martin-fowler]:http://martinfowler.com/eaaCatalog/dataTransferObject.html
