<template>
  <div class="left side-menu">
    <div class="slimscroll-menu" id="remove-scroll">
      <div id="sidebar-menu">
        <ul class="metismenu" id="side-menu">
          <li class="menu-title">Main</li>
          <li v-for="(menu,index) in getAllUserMenuForSidebar" :key="index">
            <a href="javascript:void(0);" class="waves-effect">
              <i :class="menu.Icon"></i>
              <span>{{ menu.Name }}<span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span></span>
            </a>
            <ul class="submenu">
              <li v-for="(submenu,subIndex) in menu.menu_item" :key="subIndex">
                <router-link :to="`/${submenu.Link}`" ><i :class="submenu.Icon"></i> {{ submenu.Name }}</router-link>
              </li>
            </ul>
          </li>
        </ul>
      </div>
      <div class="clearfix"></div>
    </div>
  </div>
</template>
<script>
export default {
  data(){
    return {
      user_menu:[]
    }
  },
  mounted() {
    setTimeout(()=>{
      $("#side-menu").metisMenu();
    },3000)

    this.$store.dispatch('getAllUserMenu');
  },
  computed:{
    getAllUserMenuForSidebar(){
      return this.$store.getters.getAllUserMenu;
    },
  },
  methods:{
    //
  }
}
</script>
