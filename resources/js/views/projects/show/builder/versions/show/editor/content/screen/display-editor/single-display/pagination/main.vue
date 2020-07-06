<template>

    <div>
        
        <!-- Display Pagination Explainer -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Pagination</span> to limit your screen content and allow
            users to <span class="font-italic text-success font-weight-bold">Scroll Up</span> or 
            <span class="font-italic text-success font-weight-bold">Scroll Down</span>
        </Alert>

        <!-- Pagination Manager -->
        <div class="mb-2">

            <span class="d-block font-weight-bold text-dark mt-3 mb-3">Pagination Settings: </span>
                
            <paginationManager :builder="builder" :screen="screen" :display="display"></paginationManager>

        </div>

    </div>
    
</template>

<script>

    //  Get the pagination manager
    import paginationManager from './paginationManager.vue';

    export default {
        components: { paginationManager },
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