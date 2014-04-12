Custom Indexer Example
======================

This is based on the work of Damodar Bashyal. It is a custom Magento indexer that stores product counts for category and attribute value combinations.

##How does it work?##

When the module is first installed the Magento extension setup script will create a table called `esindexer`. I had originally intended this extension to store the index in ElasticSearch but once I got started it became obvious that wasn't necessary. Once the index is created it is super fast without introducing the complexity of ElasticSearch.

The first thing you will need to do is run the shell script shell/Lot/Esindexer.php to create the index for the first time. The indexer does not actually index anything until something is requested from the index, so I had to write a shell script to automate the process.

On mce128-dev, this is how I built the indexes for the first time:

```
cd ~/public_html/dg-dev/shell/Lot
php Esindexer.php --create --attribute "manufacturer"
php Esindexer.php --create --attribute "department"
```

Log in to MySQL and run this query:
```
SELECT esindexer_id, attr_id, LEFT(products, 20) AS products, LEFT(categories,20) AS categories, LEFT(count,20) AS count, store_id, flag, `update` FROM esindexer;
```

You should see about 45 rows. Each row is for a particular option_id that corresponds to an attribute value. The products column consists a comma-separated list of products that have that attribute value, and the categories column consists of a comma-separated list of categories that have products with that attribute value.

The count column contains JSON that stores the count of products in each category in Magento with the option_id for the row.

If you make a change to any product or category in Magento the indexer will set the `flag` column to 1, meaning that the indexer will reindex the information in the row the next time a reindex is requested. I set the index mode to manual because it is a bit slow and I wouldn't want this to run all the time.

##So how is this useful?##

Run this shell script to get a count of all Men's products in the Jackets category:
```
php Lot/Esindexer.php --category-id 4541 --option-id 17215 --attribute "department"
```

Arc`teryx products in the Jackets category:
```
php Lot/Esindexer.php --category-id 4541 --option-id 3 --attribute "manufacturer"
```

In the future this indexer could be fairly easily extended to store counts for multiple attribute value combinations, like the count of Arc`teryx Men's Jackets.

All the shell script is doing is instantiating the indexer model and then calling the getFilteredProductsCount method with the appropriate parameters. Here's how this could be integrated into our navigation:

```
$esindexer = Mage::getModel('esindexer/products');
$category_id = 4541; // Jackets
$option_id = 17215; // Men's
if ($esindexer->getFilteredProductsCount($category_id, $option_id, "department")) {
	// show this category in the nav
}
```