# ppdf
PDF creator from image files. 


# Getting Started


## Requirements
- PHP 7.4
    - <a href="https://www.php.net/manual/en/book.image.php" target="_blank">GD extension (for use of FPDF class)</a>
    - <a href="https://www.php.net/manual/en/book.mbstring.php" target="_blank">MB string extension</a>
    - <a href="https://www.php.net/manual/en/book.curl.php" target="_blank">CURL extension</a>
    - <a href="https://www.php.net/manual/en/book.fileinfo.php" target="_blank">FINFO extension</a>
- Apache HTTP Server 2.0

---
<h2 id="installation">Installation</h2>
At the moment the only installation mode is manual, but it requires very easy steps:
- Unzip the spf folder in your favorite location.
- Copy the <code>spf.settings.php</code> file into your php include path directory.

---
<h2 id="directoryStructure">Directory structure</h2>
![Alt text](resources\folders.png)
- <code>api</code>: contains base class for API implementation.
- <code>app</code>: contains the source code of your applications. Here you can find also a sample application.
- <code>controller</code>: contains the base class of the MVC controller class. [See Class Reference](@ref spf::controller::_spfController).
- <code>dispatcher</code>: contains the base class of the dispatcher service. [See Class Reference](@ref spf::dispatcher::_spfDispatcher).
- <code>include</code>: contains the [autoloader](#autoloader) function. 
- <code>model</code>: contains the base classes for interface with database. See [paragraph](#dataProvider) for details. Moreover, here is located the abstract class of MVC model class. [See Class Reference](@ref spf::model::_spfModel).
- <code>modules</code>: contains misc [utility modules](#additionalModules). 
- <code>public</code>: contains the front-end pages of application, included JavaScript, CSS and resources files. 
- <code>session</code>: contains the base class for managing the user sessions. [See Class Reference](@ref spf::session::_spfSession).  
- <code>settings</code>: contains a facility class for get settings from [configuration files](#configuration).  [See Class Reference](@ref spf::settings::SPF_Settings). 
- <code>view</code>: contains the MVC view class and relative template classes. See [paragraph](#viewAndTemplates) for more details.

---
<h2 id="configuration">Configuration</h2>

---
<h2 id="dataProvider">Data Provider</h2>
SPF provides 2 abstract classes: 
- `_spfDbConnector`: it provides the connection to the DB and returns the associated instance (resource). [See Class Reference](@ref spf::model::_spfDbConnector).
- `_spfDataProvider`: if provides basic, high-level interface methods for `Inquiry`, `Insert`, `Update` and `Delete` operations. [See Class Reference](@ref spf::model::_spfDataProvider).  

For both of them the relative real class must be implemented, which uses the interface to the type of database to be used (MySQL, Oracle, etc.).  

For **MySQL database**, the real classes are already provided: 
- `spfDbConnectorMysqli`:  [See Class Reference](@ref spf::model::spfDbConnectorMysqli).
- `spfDataProviderMysqli`:  [See Class Reference](@ref spf::model::spfDataProviderMysqli).

---
<h2 id="autoloader">Autoloader, namespaces and naming conventions</h2>
The [autoloader](@ref spfAutoloader()) function references classes basing on namespaces and corresponding folder name.  
For example, the base class of the controller (named `_spfController`) is located into `"spf/controller/"` folder, so the namespaces **must be** `"spf\\controller"`.   
So, if you want to use the controller class from another file, you simply have to add `"use spf\\controller\_spfController;"` into the top of your file.
By this way, the Autoloader function can search the file containing the class, and invoke it.  
Furthermore, each class **must be** located into a file named `className.class.php`, where `className` is the name of the class. In our example, the `_spfController` class file is `_spfController.class.php`.  
Ususally you shouldn't need to change this autoloader function.  

Conventionally, for consistency with the style with which the framework is written, you should always use the **camelCase** style for classes, functions, members and file names. Moreover, the name of the abstract base classes of the SPF Framework, that we expect you will extend in your application, starts with an underscore <code>"_"</code>.  

---
<h2 id="firstApplication">Building first application</h2>

---
<h2 id="additionalModules">Additional Modules</h2>
Each module is a self-consistent library or class which doesn't need the rest of the Framework to work.
### SFPDF
### Iterable Collection
### Logger
### Mailer
### Tree data sctructure
### PHP Excel

---
<h2 id="viewAndTemplates">View and Templates</h2>

