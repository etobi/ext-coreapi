## TYPO3 Extension 'coreapi'

The EXT:coreapi should provide a simple to use API for common core features. Goal is to be able to do the most common tasks by CLI instead of doing it in the backend/browser.

Beside of CLI commands, EXT:coreapi provides service classes, which can be used in your own implementation/extension.

Checkout the project website at forge.typo3.org:
	http://forge.typo3.org/projects/show/extension-coreapi

### Tasks
* DatabaseApi
	* databaseCompare
* CacheApi
	* clearAllCaches
	* clearPageCache
	* clearConfigurationCache
* ExtensionApi
	* info
	* listInstalled
	* updateList from TER
	* fetch an extension from TER
	* import an extension
	* create upload folders
	* configure extension
* SiteApi
	* info
	* createSysNews

#### planned/comming soon

* Backend
	* manage users (list, create, update, delete)
	* lock/unlock the TYPO3 backend
* PageTree
	* print/get
* DataApi
 	* generic list/create/update/delete records (and not doing the plain SQL, but using the DataHandler (aka tcemain)!)
	* getRecordsByPid
	* create a database dump (exclude "temporary" tables like caches, sys_log, ...)
* ReportsApi
	* run/check the reports from the reports module
* ConfigurationApi
	* list, get and set TYPO3 configurations


### CLI call: ###

Make sure you have a backend user called `_cli_lowlevel`

If you want to use the cache clearing commands, you need to add the following snippet to the TSconfig field of this backend user:

	options.clearCache.all=1
	options.clearCache.pages=1

This will show you all available calls
	./typo3/cli_dispatch.phpsh extbase help
