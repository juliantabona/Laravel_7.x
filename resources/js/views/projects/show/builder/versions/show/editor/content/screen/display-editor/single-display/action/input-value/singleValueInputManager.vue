<template>

    <div>

        <div class="d-flex bg-grey-light border mt-2 p-2">

            <span class="font-weight-bold mr-1 mt-1">Reference:</span>

            <!-- Reference Name Input -->
            <referenceNameInput 
                v-model="display.content.action.input_value.single_value_input.reference_name"
                :builder="builder" :screen="screen" :display="display">
            </referenceNameInput>
            
        </div>
                
        <div class="bg-grey-light border mt-2 p-2">

            <screenAndDisplaySelector 
                :link="display.content.action.input_value.single_value_input.link" 
                :builder="builder" :screen="screen" :display="display">
            </screenAndDisplaySelector>

        </div>

    </div>
    
</template>

<script>

    import referenceNameInput from './../../../../referenceNameInput.vue';
    import screenAndDisplaySelector from './../../../../screenAndDisplaySelector.vue';

    export default {
        components: { referenceNameInput, screenAndDisplaySelector },
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
            },
        }
    };
  
</script>