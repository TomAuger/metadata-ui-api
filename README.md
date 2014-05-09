WordPress Metadata UI API
=======================

An API for building form UI for WordPress content types (post types, users, comments, Settings options, etc.).

This is a project of the [WordPress core metadata team](http://make.wordpress.org/core/components/options-meta/).

## Documentation

For this branch there is no documentation ready yet.

## Example:

```php
	/**
	 * Example init of fields
	 */
    function example_init()  {

		register_post_type( 'pm_solution',  array(
			'label'   =>  __( 'Solutions',  'pm-sherpa' ),
			'public'  =>  true,
			'rewrite' =>  true,
			'form'    =>  'after-title'
		) );

		register_post_field( 'website', 'pm_solution',  array(
			'type'              =>  'url',
			'label'             =>  __( 'Website',  'pm-sherpa' ),
			'html_placeholder'  =>  'http://www.example.com',
			'html_size'         =>  50
		) );

		register_post_field( 'tagline', 'pm_solution',  array(
			'label'     =>  __( 'Tagline',  'pm-sherpa' ),
			'html_size' =>  50
		) );

		register_post_field( 'blurb', 'pm_solution',  array(
			'type'      =>  'textarea',
			'label'     =>  __( 'Blurb',  'pm-sherpa' ),
			'html_size' =>  160
		) );

	}
	add_action( 'init', 'example_init' );
```

##FAQ

### Q: No Hooks in Work-in-Progress?
A: At this point we are avoid adding any hooks so we can first finalize the architecture.

## Contributing

We welcome contributions. That being said, be aware that any functionality that is missing, we're probably already aware of. Take a look through existing issues, and feel free to open up a new one to discuss the changes you'd like to make. After discussion in an issue we'll be happy to review a pull request.

## DISCLAIMER

This software is in alpha until otherwise noted. There is no guarantee on backwards compatibility nor a warrantee. It is not recommended to be used on any production site.

##LICENSE

GPLv2 or later. See [License](LICENSE.txt).