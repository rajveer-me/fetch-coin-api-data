wp.blocks.registerBlockType('crypto/widget-block', {
    title: 'Crypto Widget',
    icon: 'chart-line',
    category: 'widgets',
    edit: function () {
        return wp.element.createElement('p', {}, 'Crypto Widget will display here.');
    },
    save: function () {
        return null; // Block is dynamic and rendered via PHP
    }
});
