<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>POST data</title>
</head>
<body onload="document.getElementsByTagName('input')[0].click();">

<noscript>
    <p>
        <strong>
            <?php use SimpleSAML\Module\perun\Post;

            echo $this->t('{perun:post:note}'); ?>
        </strong>
        <?php echo $this->t('{perun:post:browser_not_support_javascript}'); ?>
    </p>
</noscript>

<form method="post" action="<?php echo htmlspecialchars($this->data['destination']); ?>">
    <!-- Need to add this element and call click method, because calling submit()
    on the form causes failed submission if the form has another element with name or id of submit.
    See: https://developer.mozilla.org/en/DOM/form.submit#Specification -->
    <input type="submit" style="display:none;"/>
    <?php
    if (array_key_exists('post', $this->data)) {
        $post = $this->data['post'];
    } else {
        // For backwards compatibility
        assert(array_key_exists('response', $this->data));
        assert(array_key_exists('RelayStateName', $this->data));
        assert(array_key_exists('RelayState', $this->data));

        $post = [
            'SAMLResponse' => $this->data['response'],
            $this->data['RelayStateName'] => $this->data['RelayState'],
        ];
    }

    foreach ($post as $name => $value) {
        Post::printItem($name, $value);
    }
    ?>

    <noscript>
        <button type="submit" class="btn"><?php echo $this->t('{perun:perun:continue}'); ?></button>
    </noscript>
</form>

</body>
</html>
