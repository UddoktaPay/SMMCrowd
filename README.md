# UddoktaPay: Simplifying Payments for SMMCrowd

Integrating the UddoktaPay payment module into SMMCrowd has never been easier. Follow these steps to seamlessly set up the module:

1. **Create UddoktaPay Folder:** Begin by creating a folder named `UddoktaPay` in the following directory:
   ```
   application/app/Http/Controllers/Gateway
   ```

2. **Upload ProcessController.php:** Upload the `ProcessController.php` file to the newly created location:
   ```
   application/app/Http/Controllers/Gateway/UddoktaPay
   ```

3. **Update ipn.php:** Add the following code snippet at the end of the `ipn.php` file located in `application/routes`:
   ```php
   Route::any('uddoktapay', 'UddoktaPay\ProcessController@ipn')->name('UddoktaPay');
   ```

4. **Import Database.sql:** In PhpMyAdmin, select the SMM Panel Database and import the `database.sql` file to ensure all necessary configurations are in place.

Enjoy the enhanced payment capabilities brought to you by UddoktaPay, simplifying transactions within SMMCrowd.