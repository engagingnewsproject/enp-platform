</div>


</main>

<!--<script src="<?php echo $url;?>/dist/js/handlebars.runtime.js"></script>
<script src="<?php echo $url;?>/dist/js/templates.js"></script>
<script src="<?php echo $url;?>/dist/js/scripts.js"></script>-->

<script src="<?php echo $url;?>/dist/js/cme-tree.min.js"></script>
<script>
var treeOptions = {
        slug: '<?php echo $tree_slug;?>',
        container: document.getElementById('cme-tree')
};
// you can access all your trees with var trees
createTree(treeOptions);
</script>

</body>
</html>
