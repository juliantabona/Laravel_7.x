<template>

    <div :style="[defaultDivStyles, divStyles]">
        <img src="/assets/icons/star_loader_black.svg" alt="Loader" :style="[defaultImageStyles, imageStyles]">
        <slot>Loading...</slot>
    </div>

</template>

<script>

  export default {
    props: {
        divStyles: {
            type: Object,
            default: null
        },
        imageStyles: {
            type: Object,
            default: null
        },
        background: {
            type: Boolean,
            default: true
        }
    },
    data(){
        return {
            defaultDivStyles: {
                textAlign: 'center'
            },
            defaultImageStyles: {
                width: '40px'
            }
        }
    }
  };
  
</script>