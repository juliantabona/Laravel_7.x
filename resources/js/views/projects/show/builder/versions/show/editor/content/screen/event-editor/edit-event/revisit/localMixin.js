// Custom defined mixin object
var mixin = {
    data(){
        return {
            revisitTypes: [
                {
                    name: 'Home Revisit',
                    value: 'home_revisit'
                },
                {
                    name: 'Screen Revisit',
                    value: 'screen_revisit'
                },
                {
                    name: 'Marked Revisit',
                    value: 'marked_revisit'
                }
            ],
            triggerTypes: [
                {
                    name: 'Automatic',
                    value: 'automatic'
                },
                {
                    name: 'Manual',
                    value: 'manual'
                }
            ]
        }
    }
}

export default mixin;