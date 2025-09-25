<template>
  <div class="tree-view-parent">
    <div class="row">
      <div class="col-md-12">
        <ul id="tree1">
          <li v-for="(item,index) in treeList" :key="index"><a href="#" class="dept-tree">{{ item.Name }}</a>
            <ul>
              <li v-for="(menuItem,indexItem) in item.menu_item" :key="indexItem" class="group-tree">
                <div class="custom-control checkbox custom-control-inline">
                  <input type="checkbox" :id="`menuPermission${menuItem.Id}`" v-model="menuPermission[menuItem.Id]"
                         class="custom-control-input">
                  <label class="custom-control-label group-tree" :for="`menuPermission${menuItem.Id}`">{{
                      menuItem.Name
                    }}</label>
                </div>
              </li>
            </ul>
          </li>
        </ul>
      </div>
      <div class="col-12">
        <button class="btn btn-primary mt-2" @click="save">Save Permissions</button>
      </div>
    </div>
  </div>
</template>
<script>
import {Common} from "../../mixins/common";
import {baseurl} from '../../base_url'
export default {
  mixins: [Common],
  props: ['treeList', 'userId', 'permission'],
  data() {
    return {
      menuPermission: []
    }
  },
  methods: {
    save() {
        axios.post(baseurl + "api/save-user-menu-permission",{permission: this.menuPermission, userId: this.userId}).then(response => {
            console.log(response)
            this.$toaster.success('Data Successfully Updated');
        }).catch(e => {
            this.isLoading = false;
        });
    }
  },
  created() {
    this.menuPermission = this.permission;
  },
  mounted() {
    $.fn.extend({
      treed: function (o) {

        var openedClass = 'ti-arrow-circle-down';
        var closedClass = 'ti-arrow-circle-right';

        if (typeof o != 'undefined') {
          if (typeof o.openedClass != 'undefined') {
            openedClass = o.openedClass;
          }
          if (typeof o.closedClass != 'undefined') {
            closedClass = o.closedClass;
          }
        }
        var tree = $(this);
        tree.addClass("tree");
        tree.find('li').has("ul").each(function () {
          var branch = $(this); //li with children ul
          branch.prepend("<i class='indicator glyphicon " + closedClass + "'></i>");
          branch.addClass('branch');
          $(this).children().children().toggle();
          branch.on('click', function (e) {
            if (this == e.target) {
              var icon = $(this).children('i:first');
              icon.toggleClass(openedClass + " " + closedClass);
              $(this).children().children().toggle();
            }
          })
          branch.children().children().toggle();
        });
        tree.find('.branch .indicator').each(function () {
          $(this).on('click', function () {
            console.log('Test 2')
            $(this).closest('li').click();
          });
        });
        tree.find('.branch>a').each(function () {
          $(this).on('click', function (e) {
            console.log('Test 3')
            $(this).closest('li').click();
            e.preventDefault();
          });
        });
        tree.find('.branch>button').each(function () {
          $(this).on('click', function (e) {
            console.log('Test 4')
            $(this).closest('li').click();
            e.preventDefault();
          });
        });
      }
    });
    $('#tree1').treed();
  }
}

</script>
<style lang="scss">
.tree-view-parent {
  .dept-tree {
    font-size: 16px;
    color: #0d6efd;
    font-weight: 600;
  }

  .sub-dept-tree {
    color: #198754;
    font-size: 15px;
    font-weight: 600;
  }

  .category-tree {
    color: #219fb9;
  }

  .sub-category-tree {
    color: #6610f2;
  }

  .group-tree {
    color: rgba(42, 49, 66, .7);
  }

  .tree, .tree ul {
    margin: 0;
    padding: 0;
    list-style: none
  }

  .tree ul {
    margin-left: 1em;
    position: relative
  }

  .tree ul ul {
    margin-left: .5em
  }

  .tree ul:before {
    content: "";
    display: block;
    width: 0;
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    border-left: 1px solid
  }

  .tree li {
    margin: 0;
    padding: 0 1em;
    line-height: 2em;
    position: relative
  }

  .tree ul li:before {
    content: "";
    display: block;
    width: 10px;
    height: 0;
    border-top: 1px solid;
    margin-top: -1px;
    position: absolute;
    top: 1em;
    left: 0
  }

  .tree ul li:last-child:before {
    background: #fff;
    height: auto;
    top: 1em;
    bottom: 0
  }

  .indicator {
    margin-right: 5px;
  }

  .tree li a {
    text-decoration: none;
    color: #369;
  }

  .tree li button, .tree li button:active, .tree li button:focus {
    text-decoration: none;
    color: #369;
    border: none;
    background: transparent;
    margin: 0px 0px 0px 0px;
    padding: 0px 0px 0px 0px;
    outline: 0;
  }
}
</style>
