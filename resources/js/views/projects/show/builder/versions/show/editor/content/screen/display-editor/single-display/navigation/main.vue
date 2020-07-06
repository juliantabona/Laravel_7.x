<template>

    <!-- Screen Display Navigation -->
    <div>

        <!-- Display Navigation Explainer -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Navigation</span> to load the next
            repeating item. You can provide instructions for <span class="font-italic text-success font-weight-bold">Forward Navigation</span>
            to load the next item and <span class="font-italic text-success font-weight-bold">Backward Navigation</span> to load the
            previous item.
        </Alert>

        <!-- Before Display Response Navigation -->
        <div class="mb-2">

            <span class="d-block font-weight-bold text-dark">Forward Navigation:</span>

            <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">
                
                <navigationManager :navigations="display.content.screen_repeat_navigation.forward_navigation" :builder="builder" :screen="screen" :display="display"></navigationManager>

            </div>

        </div>

        <!-- After Display Response Navigation -->
        <div class="mb-2">

            <span class="d-block font-weight-bold text-dark">Backward Navigation:</span>

            <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">
                
                <navigationManager :navigations="display.content.screen_repeat_navigation.backward_navigation" :builder="builder" :screen="screen" :display="display"></navigationManager>

            </div>

        </div>

    </div>
    
</template>

<script>

    import navigationManager from './navigation-manager/main.vue';

    export default {
        components: { navigationManager },
        props: { 
            screen: {
                type: Object,
                default: null
            },
            display: {
                type: Object,
                default: null
            },
            builder: {
                type: Object,
                default: null
            }
        },
        data(){
            return {
                
            }
        }
    };
  
</script>