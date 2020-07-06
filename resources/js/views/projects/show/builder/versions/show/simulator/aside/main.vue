<template>

    <!-- Display simulator menu options -->
    <Menu :active-name="activeMenu" theme="light" class="w-100">

        <MenuItem name="debugger" @click.native="$emit('showDebugger')">
            <Icon type="ios-bug-outline" :size="20"/>
            <span>Debugger</span>
        </MenuItem>
        
        <MenuItem name="subscriptions" @click.native="$emit('showSubscriptions')">
            <Icon type="ios-contact-outline" :size="20"/>
            <span>Subscriber</span>
        </MenuItem>
        
        <MenuItem name="settings" @click.native="$emit('showSettings')">
            <Icon type="ios-settings-outline" :size="20"/>
            <span>Settings</span>
        </MenuItem>

    </Menu>
    
</template>

<script>

    export default {
        props: {
            project: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            }
        },
        components: { 
            
        },
        data(){
            return {
                activeMenu: 'debugger'
            }
        }, 
        computed: {

        },
        methods: {

        }
    };
  
</script>