<?php

class AkActsAsNestedInterval extends AkObserver
{
    /**
      *  This act implements a nested-interval tree. You can find all descendants or all
      *  ancestors with just one select query. You can insert and delete records without
      *  a full table update.
      *
      *  This act requires a "parent_id" foreign key column, and "lftp" and "lftq"
      *  integer columns. If your database does not support stored procedures then you
      *  also need "rgtp" and "rgtq" integer columns, and if your database does not
      *  support functional indexes then you also need a "rgt" float column. The "lft"
      *  float column is optional.
      *
      *  Example:
      *    AkInstaller::createTable( 'regions',
      *      parent_id,
      *      lftp,
      *      lftq,
      *      rgtp,
      *      rgtq,
      *      lft,
      *      rgt,
      *      name'
      *    );
      *    
      *  The size of the tree is limited by the precision of the integer and floating
      *  point data types in the database.
      *
      *  This act provides these named scopes:
      *    
      *     getRoots -- returns roots of tree.
      *     getPreorderRecords -- returns records for preorder traversal.
      *
      *  This act provides these instance methods:
      * 
      *    getParent -- returns parent of record.
      *    getChildren -- returns children of record.
      *    getAncestors -- returns scoped ancestors of record.
      *    getDescendants -- returns scoped descendants of record.
      *    getDepth -- returns depth of record.
      *
      *  Example:
      *
      *         class Region extends ActiveRecord{
      *             public $acts_as = 'nested_interval';
      *         }
      *
      *    $earth = $Region->create(array('name' => "Earth"));
      *    $oceania = $Region->create(array('name' => "Oceania", 'parent' => $earth));
      *    $australia = $Region->create(array('name' => "Australia", 'parent' => $oceania));
      *    $new_zealand = new Region(array('name' => "New Zealand"));
      *    $oceania->addChildren($new_zealand);
      *    $earth->getDescendants();
      *    # => array($oceania, $australia, $new_zealand)
      *    $earth->getChildren();
      *    # => array($oceania)
      *    $oceania->getChildren();
      *    # => array($australia, $new_zealand)
      *    $oceania->getDepth();
      *    # => 1
      *    $australia->getParent();
      *    # => $oceania
      *    $new_zealand->getAncestors();
      *    # => array($earth, $oceania);
      *    $Region->getRoots();
      *    # => array($earth)
      *
      *  The "mediant" of two rationals is the rational with the sum of the two
      *  numerators for the numerator, and the sum of the two denominators for the
      *  denominator (where the denominators are positive). The mediant is numerically
      *  between the two rationals. Example: 3 / 5 is the mediant of 1 / 2 and 2 / 3,
      *  and 1 / 2 < 3 / 5 < 2 / 3.
      *
      *  Each record "covers" a half-open interval (lftp / lftq, rgtp / rgtq]. The tree
      *  root covers (0 / 1, 1 / 1]. The first child of a record covers interval
      *  (mediant{lftp / lftq, rgtp / rgtq}, rgtp / rgtq]; the next child covers
      *  interval (mediant{lftp / lftq, mediant{lftp / lftq, rgtp / rgtq}},
      *                     mediant{lftp / lftq, rgtp / rgtq}].
      *
      *  With this construction each lftp and lftq are relatively prime and the identity
      *  lftq * rgtp = 1 + lftp * rgtq holds.
      *
      *  Example:
      *                 0/1                           1/2   3/5 2/3                 1/1
      *    earth         (-----------------------------------------------------------]
      *    oceania                                     (-----------------------------]
      *    australia                                             (-------------------]
      *    new zealand                                       (---]
      *
      *  The descendants of a record are those records that cover subintervals of the
      *  interval covered by the record, and the ancestors are those records that cover
      *  superintervals.
      *
      *  Only the left end of an interval needs to be stored, since the right end can be
      *  calculated (with special exceptions) using the above identity:
      *    rgtp = x
      *    rgtq = (x * lftq - 1) / lftp
      *  where x is the inverse of lftq modulo lftp.
      *
      *  Similarly, the left end of the interval covered by the parent of a record can
      *  be calculated using the above identity:
      *    lftp = (x * lftp - 1) / lftq
      *    lftq = x
      *  where x is the inverse of lftp modulo lftq.
      *
      *  To move a record from old.lftp, old.lftq to new.lftp, new.lftq, apply this
      *  linear transform to lftp, lftq of all descendants:
      *    lftp = (old.lftq * new.rgtp - old.rgtq * new.lftp) * lftp
      *             + (old.rgtp * new.lftp - old.lftp * new.rgtp) * lftq
      *    lftq = (old.lftq * new.rgtq - old.rgtq * new.lftq) * lftp
      *             + (old.rgtp * new.lftq - old.lftp * new.rgtq) * lftq
      *
      *  You should acquire a table lock before moving a record.
      *
      *  Example:
      *    $pacific = $Region->create(array('name' => "Pacific", 'parent' => $earth));
      *    $oceania->getParent( $pacific );
      *    $oceania->save();
      *
      *  Acknowledgement:
      *    http://arxiv.org/html/cs.DB/0401014 by Vadim Tropashko.
      *
     */

    /**
    * Configuration options are:
    *
    * * +parent_column+ - specifies the column name to use for keeping the position integer (default: parent_id)
    * * +left_column+ - column name for left boundary data, default "lft"
    * * +right_column+ - column name for right boundary data, default "rgt"
    * * +scope+ - restricts what is to be considered a list.
    *   Example: <tt>actsAsList(array('scope' => array('todo_list_id = ? AND completed = 0',$todo_list_id)));</tt>
    */

    public $default_options = array(
    'columns' => array(
    'left' => 'lft',
    'left_p' => 'lftp',
    'left_q' => 'lftq',
    'right' => 'rgt',
    'right_p' => 'rgtp',
    'right_q' => 'rgtq',
    'parent' => 'parent_id',
    ),
    'scope' => null
    );


    public $_ActiveRecordInstance;

    /**
     * This act implements a nested-interval tree. You can find all descendants
     * or all ancestors with just one select query. You can insert and delete
     * records without a full table update.
     */
    public function __construct(&$ActiveRecordInstance)
    {
        $this->_ActiveRecordInstance = $ActiveRecordInstance;
    }

    public function init($options = array())
    {
        $this->options = array($this->default_options, $options);
        return $this->_ensureIsActiveRecordInstance($this->_ActiveRecordInstance);
    }

    /**
     * Returns modular multiplicative inverse.
     *  Examples:
     *      2.inverse(7) # => 4
     *      4.inverse(7) # => 2
     */
    static function modular_multiplicative_inverse($number, $multiplier)
    {
        $u = $multiplier;
        $v = $number;
        $x = 0;
        $y = 1;

        while($v != 0){
            $q = $r = divmod($u, $v);
            $x = $y = $x - $q * $y;
            $u = $v = $r;
        }

        if(abs($u) == 1){
            $x < 0 ? $x + $multiplier : $x;
        }
    }
    
    /*


    def extended_gcd(a, b):
    x, last_x = 0, 1
    y, last_y = 1, 0

    while b:
    quotient = a // b
    a, b = b, a % b
    x, last_x = last_x - quotient*x, x
    y, last_y = last_y - quotient*y, y

    return (last_x, last_y, a)

    def inverse_mod(a, m):
    x, q, gcd = extended_gcd(a, m)

    if gcd == 1:
    # x is the inverse, but we want to be sure a positive number is returned.
    return (x + m) % m
    else:
    # if gcd != 1 then a and m are not coprime and the inverse does not exist.
    return None






    class Integer
    def inverse($multiplier)
    u, v = $multiplier, self
    x, y = 0, 1
    while v != 0
    q, r = u.divmod(v)
    x, y = y, x - q * y
    u, v = v, r
    end
    if u.abs == 1
    x < 0 ? x + $multiplier : x
    end
    end
    end

    module ActiveRecord::Acts
    module NestedInterval
    def self.included(base)
    base.extend(ClassMethods)
    end


    module ClassMethods
    # The +options+ hash can include:
    # * <tt>:foreign_key</tt> -- the self-reference foreign key column name (default :parent_id).
    # * <tt>:scope_columns</tt> -- an array of columns to scope independent trees.
    # * <tt>:lft_index</tt> -- whether to use functional index for lft (default false).
    def acts_as_nested_interval(options = {})
    cattr_accessor :nested_interval_foreign_key
    cattr_accessor :nested_interval_scope_columns
    cattr_accessor :nested_interval_lft_index
    self.nested_interval_foreign_key = options[:foreign_key] || :parent_id
    self.nested_interval_scope_columns = Array(options[:scope_columns])
    self.nested_interval_lft_index = options[:lft_index]
    belongs_to :parent, :class_name => name, :foreign_key => nested_interval_foreign_key
    has_many :children, :class_name => name, :foreign_key => nested_interval_foreign_key, :dependent => :destroy
    named_scope :roots, :conditions => {nested_interval_foreign_key => nil}
    if columns_hash["rgt"]
    named_scope :preorder, :order => %(rgt DESC, lftp ASC)
    elsif columns_hash["rgtp"] && columns_hash["rgtq"]
    named_scope :preorder, :order => %(1.0 * rgtp / rgtq DESC, lftp ASC)
    else
    named_scope :preorder, :order => %(nested_interval_rgt(lftp, lftq) DESC, lftp ASC)
    end
    class_eval do
    include ActiveRecord::Acts::NestedInterval::InstanceMethods
    alias_method_chain :create, :nested_interval
    alias_method_chain :destroy, :nested_interval
    alias_method_chain :update, :nested_interval
    if columns_hash["lft"]
    def descendants
    quoted_table_name = self.class.quoted_table_name
    nested_interval_scope.scoped :conditions => %(#{lftp} < #{quoted_table_name}.lftp AND #{quoted_table_name}.lft BETWEEN #{1.0 * lftp / lftq} AND #{1.0 * rgtp / rgtq})
    end
    elsif nested_interval_lft_index
    def descendants
    quoted_table_name = self.class.quoted_table_name
    nested_interval_scope.scoped :conditions => %(#{lftp} < #{quoted_table_name}.lftp AND 1.0 * #{quoted_table_name}.lftp / #{quoted_table_name}.lftq BETWEEN #{1.0 * lftp / lftq} AND #{1.0 * rgtp / rgtq})
    end
    elsif connection.adapter_name == "MySQL"
    def descendants
    quoted_table_name = self.class.quoted_table_name
    nested_interval_scope.scoped :conditions => %((#{quoted_table_name}.lftp != #{rgtp} OR #{quoted_table_name}.lftq != #{rgtq}) AND #{quoted_table_name}.lftp BETWEEN 1 + #{quoted_table_name}.lftq * #{lftp} DIV #{lftq} AND #{quoted_table_name}.lftq * #{rgtp} DIV #{rgtq})
    end
    else
    def descendants
    quoted_table_name = self.class.quoted_table_name
    nested_interval_scope.scoped :conditions => %((#{quoted_table_name}.lftp != #{rgtp} OR #{quoted_table_name}.lftq != #{rgtq}) AND #{quoted_table_name}.lftp BETWEEN 1 + #{quoted_table_name}.lftq * CAST(#{lftp} AS BIGINT) / #{lftq} AND #{quoted_table_name}.lftq * CAST(#{rgtp} AS BIGINT) / #{rgtq})
    end
    end
    end
    end
    end

    module InstanceMethods
    def set_nested_interval(lftp, lftq)
    self.lftp, self.lftq = lftp, lftq
    self.rgtp = rgtp if has_attribute?(:rgtp)
    self.rgtq = rgtq if has_attribute?(:rgtq)
    self.lft = lft if has_attribute?(:lft)
    self.rgt = rgt if has_attribute?(:rgt)
    end

    # Creates record.
    def create_with_nested_interval
    if read_attribute(nested_interval_foreign_key).nil?
    set_nested_interval 0, 1
    else
    set_nested_interval *parent.lock!.next_child_lft
    end
    create_without_nested_interval
    end

    # Destroys record.
    def destroy_with_nested_interval
    lock! rescue nil
    destroy_without_nested_interval
    end

    def nested_interval_scope
    conditions = {}
    nested_interval_scope_columns.each do |column_name|
    conditions[column_name] = send(column_name)
    end
    self.class.scoped(:conditions => conditions)
    end

    # Updates record, updating descendants if parent association updated,
    # in which case caller should first acquire table lock.
    def update_with_nested_interval
    if read_attribute(nested_interval_foreign_key).nil?
    set_nested_interval 0, 1
    elsif !parent.updated?
    db_self = self.class.find(id, :lock => true)
    write_attribute(nested_interval_foreign_key, db_self.read_attribute(nested_interval_foreign_key))
    set_nested_interval db_self.lftp, db_self.lftq
    else
    # No locking in this case -- caller should have acquired table lock.
    db_self = self.class.find(id)
    db_parent = self.class.find(read_attribute(nested_interval_foreign_key))
    if db_parent.lftp == db_self.lftp && db_parent.lftq == db_self.lftq \
    || db_parent.lftp > db_parent.lftq * db_self.lftp / db_self.lftq \
    && db_parent.lftp <= db_parent.lftq * db_self.rgtp / db_self.rgtq \
    && (db_parent.lftp != db_self.rgtp || db_parent.lftq != db_self.rgtq)
    errors.add :parent_id, "is descendant"
    raise ActiveRecord::RecordInvalid, self
    end
    set_nested_interval *parent.next_child_lft
    mysql_tmp = "@" if connection.adapter_name == "MySQL"
    cpp = db_self.lftq * rgtp - db_self.rgtq * lftp
    cpq = db_self.rgtp * lftp - db_self.lftp * rgtp
    cqp = db_self.lftq * rgtq - db_self.rgtq * lftq
    cqq = db_self.rgtp * lftq - db_self.lftp * rgtq
    db_descendants = db_self.descendants
    if has_attribute?(:rgtp) && has_attribute?(:rgtq)
    db_descendants.update_all %(
    rgtp = #{cpp} * rgtp + #{cpq} * rgtq,
    rgtq = #{cqp} * #{mysql_tmp}rgtp + #{cqq} * rgtq
    ), mysql_tmp && %(@rgtp := rgtp)
    db_descendants.update_all %(rgt = 1.0 * rgtp / rgtq) if has_attribute?(:rgt)
    end
    db_descendants.update_all %(
    lftp = #{cpp} * lftp + #{cpq} * lftq,
    lftq = #{cqp} * #{mysql_tmp}lftp + #{cqq} * lftq
    ), mysql_tmp && %(@lftp := lftp)
    db_descendants.update_all %(lft = 1.0 * lftp / lftq) if has_attribute?(:lft)
    end
    update_without_nested_interval
    end

    def ancestors
    sqls = [%(NULL)]
    p, q = lftp, lftq
    while p != 0
    x = p.inverse(q)
    p, q = (x * p - 1) / q, x
    sqls << %(lftq = #{q} AND lftp = #{p})
    end
    nested_interval_scope.scoped :conditions => sqls * %( OR )
    end

    # Returns depth by counting ancestors up to 0 / 1.
    def depth
    n = 0
    p, q = lftp, lftq
    while p != 0
    x = p.inverse(q)
    p, q = (x * p - 1) / q, x
    n += 1
    end
    n
    end

    def lft
    1.0 * lftp / lftq
    end

    def rgt
    1.0 * rgtp / rgtq
    end

    # Returns numerator of right end of interval.
    def rgtp
    case lftp
    when 0
    1
    when 1
    1
    else
    lftq.inverse(lftp)
    end
    end

    # Returns denominator of right end of interval.
    def rgtq
    case lftp
    when 0
    1
    when 1
    lftq - 1
    else
    (lftq.inverse(lftp) * lftq - 1) / lftp
    end
    end

    # Returns left end of interval for next child.
    def next_child_lft
    if child = children.find(:first, :order => %(lftq DESC))
    return lftp + child.lftp, lftq + child.lftq
    else
    return lftp + rgtp, lftq + rgtq
    end
    end
    end
    end
    end

    */



    public function _ensureIsActiveRecordInstance(&$ActiveRecordInstance)
    {
        if(is_object($ActiveRecordInstance) && method_exists($ActiveRecordInstance,'actsLike')){
            $this->_ActiveRecordInstance =& $ActiveRecordInstance;
            if(!$this->_ActiveRecordInstance->hasColumn($this->_parent_column_name) || !$this->_ActiveRecordInstance->hasColumn($this->_left_column_name) || !$this->_ActiveRecordInstance->hasColumn($this->_right_column_name)){
                trigger_error(Ak::t(
                'The following columns are required in the table "%table" for the model "%model" to act as a Nested Set: "%columns".',array(
                '%columns'=>$this->getParentColumnName().', '.$this->getLeftColumnName().', '.$this->getRightColumnName(),'%table'=>$this->_ActiveRecordInstance->getTableName(),'%model'=>$this->_ActiveRecordInstance->getModelName())),E_USER_ERROR);
                unset($this->_ActiveRecordInstance->nested_set);
                return false;
            }else{
                $this->observe(&$ActiveRecordInstance);
            }
        }else{
            trigger_error(Ak::t('You are trying to set an object that is not an active record.'), E_USER_ERROR);
            return false;
        }
        return true;
    }

    public function getType()
    {
        return 'nested set';
    }

    public function getScopeCondition()
    {
        if (!empty($this->variable_scope_condition)){
            return $this->_ActiveRecordInstance->getVariableSqlCondition($this->variable_scope_condition);

            // True condition in case we don't have a scope
        }elseif(empty($this->scope_condition) && empty($this->scope)){
            $this->scope_condition = ($this->_ActiveRecordInstance->_db->type() == 'postgre') ? 'true' : '1';
        }elseif (!empty($this->scope)){
            $this->setScopeCondition(join(' AND ',array_map(array(&$this,'getScopedColumn'),(array)$this->scope)));
        }
        return  $this->scope_condition;
    }


    public function setScopeCondition($scope_condition)
    {
        if(!is_array($scope_condition) && strstr($scope_condition, '?')){
            $this->variable_scope_condition = $scope_condition;
        }else{
            $this->scope_condition  = $scope_condition;
        }
    }

    public function getScopedColumn($column)
    {
        if($this->_ActiveRecordInstance->hasColumn($column)){
            $value = $this->_ActiveRecordInstance->get($column);
            $condition = $this->_ActiveRecordInstance->getAttributeCondition($value);
            $value = $this->_ActiveRecordInstance->castAttributeForDatabase($column, $value);
            return $column.' '.str_replace('?', $value, $condition);
        }else{
            return $column;
        }
    }

    public function getLeftColumnName()
    {
        return $this->_left_column_name;
    }
    public function setLeftColumnName($left_column_name)
    {
        $this->_left_column_name = $left_column_name;
    }

    public function getRightColumnName()
    {
        return $this->_right_column_name;
    }
    public function setRightColumnName($right_column_name)
    {
        $this->_right_column_name = $right_column_name;
    }


    public function getParentColumnName()
    {
        return $this->_parent_column_name;
    }
    public function setParentColumnName($parent_column_name)
    {
        $this->_parent_column_name = $parent_column_name;
    }

    /**
    * Returns true is this is a root node.
    */
    public function isRoot()
    {
        $left_id = $this->_ActiveRecordInstance->get($this->getLeftColumnName());
        return ($this->_ActiveRecordInstance->get($this->getParentColumnName()) == null) && ($left_id == 1) && ($this->_ActiveRecordInstance->get($this->getRightColumnName()) > $left_id);
    }

    /**
    * Returns true is this is a child node
    */
    public function isChild()
    {
        $parent_id = $this->_ActiveRecordInstance->get($this->getParentColumnName());
        $left_id = $this->_ActiveRecordInstance->get($this->getLeftColumnName());
        return !($parent_id == 0 || is_null($parent_id)) && ($left_id > 1) && ($this->_ActiveRecordInstance->get($this->getRightColumnName()) > $left_id);
    }

    /**
    * Returns true if we have no idea what this is
    */
    public function isUnknown()
    {
        return !$this->isRoot() && !$this->isChild();
    }

    /**
    * Added a child to this object in the tree.  If this object hasn't been initialized,
    * it gets set up as a root node.  Otherwise, this method will update all of the
    * other elements in the tree and shift them to the right. Keeping everything
    * balanced.
    */
    public function addChild( &$child )
    {
        $self =& $this->_ActiveRecordInstance;
        $self->reload();
        $child->reload();
        $left_column = $this->getLeftColumnName();
        $right_column = $this->getRightColumnName();
        $parent_column = $this->getParentColumnName();

        if ($child->nested_set->isRoot()){
            trigger_error(Ak::t("Adding sub-tree isn't currently supported"),E_USER_ERROR);
        }elseif ( (is_null($self->get($left_column))) || (is_null($self->get($right_column))) ){
            // Looks like we're now the root node!  Woo
            $self->set($left_column, 1);
            $self->set($right_column, 4);

            $self->transactionStart();
            // What do to do about validation?
            if(!$self->save()){
                $self->transactionFail();
                $self->transactionComplete();
                return false;
            }

            $child->set($parent_column, $self->getId());
            $child->set($left_column, 2);
            $child->set($right_column, 3);

            if(!$child->save()){
                $self->transactionFail();
                $self->transactionComplete();
                return false;
            }
            $self->transactionComplete();
            return $child;
        }else{
            // OK, we need to add and shift everything else to the right
            $child->set($parent_column, $self->getId());
            $right_bound = $self->get($right_column);
            $child->set($left_column, $right_bound);
            $child->set($right_column, $right_bound +1);
            $self->set($right_column, $self->get($right_column) + 2);

            $self->transactionStart();
            $self->updateAll( "$left_column = ($left_column + 2)",  $this->getScopeCondition()." AND $left_column >= $right_bound" );
            $self->updateAll( "$right_column = ($right_column + 2)",  $this->getScopeCondition()." AND $right_column >= $right_bound" );
            $self->save();
            $child->save();
            $self->transactionComplete();
            return $child;
        }
    }


    /**
    * Returns the parent Object
    */
    public function &getParent()
    {
        if(!$this->isChild()){
            $result = false;
        }else{
            $result =& $this->_ActiveRecordInstance->find(
            // str_replace(array_keys($options['conditions']), array_values($this->getSanitizedConditionsArray($options['conditions'])),$pattern);
            'first', array('conditions' => " ".$this->getScopeCondition()." AND ".$this->_ActiveRecordInstance->getPrimaryKey()." = ".$this->_ActiveRecordInstance->{$this->getParentColumnName()})
            );
        }
        return $result;
    }

    /**
    * Returns an array of parent Objects this is usefull to make breadcrum like stuctures
    */
    public function &getParents()
    {
        $Ancestors =& $this->getAncestors();
        return $Ancestors;
    }


    /**
    * Prunes a branch off of the tree, shifting all of the elements on the right
    * back to the left so the counts still work.
    */
    public function beforeDestroy(&$object)
    {
        if(!empty($object->__avoid_nested_set_before_destroy_recursion)){
            return true;
        }
        if((empty($object->{$this->getRightColumnName()}) || empty($object->{$this->getLeftColumnName()})) || $object->nested_set->isUnknown()){
            return true;
        }
        $dif = $object->{$this->getRightColumnName()} - $object->{$this->getLeftColumnName()} + 1;

        $ObjectsToDelete = $object->nested_set->getAllChildren();

        $object->transactionStart();

        if(!empty($ObjectsToDelete)){
            foreach (array_keys($ObjectsToDelete) as $k){
                $Child =& $ObjectsToDelete[$k];
                $Child->__avoid_nested_set_before_destroy_recursion = true;
                if($Child->beforeDestroy()){
                    if($Child->notifyObservers('beforeDestroy') === false){
                        $Child->transactionFail();
                    }
                }else{
                    $Child->transactionFail();
                }
            }
        }

        $object->deleteAll($this->getScopeCondition().
        " AND ".$this->getLeftColumnName()." > ".$object->{$this->getLeftColumnName()}.
        " AND ".$this->getRightColumnName()." < ".$object->{$this->getRightColumnName()});

        $object->updateAll($this->getLeftColumnName()." = (".$this->getLeftColumnName()." - $dif)",
        $this->getScopeCondition()." AND ".$this->getLeftColumnName()." >= ".$object->{$this->getRightColumnName()} );

        $object->updateAll($this->getRightColumnName()." = (".$this->getRightColumnName()." - $dif )",
        $this->getScopeCondition()." AND ".$this->getRightColumnName()." >= ".$object->{$this->getRightColumnName()});


        if(!empty($ObjectsToDelete)){
            foreach (array_keys($ObjectsToDelete) as $k){
                $Child =& $ObjectsToDelete[$k];
                $Child->__avoid_nested_set_before_destroy_recursion = true;
                if(!$Child->afterDestroy() || $Child->notifyObservers('afterDestroy') === false){
                    $Child->transactionFail();
                }
            }
        }

        if($object->transactionHasFailed()){
            $object->transactionComplete();
            return false;
        }
        $object->transactionComplete();

        return true;
    }

    /**
     * on creation, set automatically lft and rgt to the end of the tree
     */
    public function beforeCreate(&$object)
    {
        $object->nested_set->_setLeftAndRightToTheEndOfTheTree();
        return true;
    }

    public function _setLeftAndRightToTheEndOfTheTree()
    {
        $left = $this->getLeftColumnName();
        $right = $this->getRightColumnName();

        $maxright = $this->_ActiveRecordInstance->maximum($right, array('conditions'=>$this->getScopeCondition()));
        $maxright = empty($maxright) ? 0 : $maxright;

        $this->_ActiveRecordInstance->set($left, $maxright+1);
        $this->_ActiveRecordInstance->set($right, $maxright+2);
    }

    /**
     * Returns the single root
     */
    public function getRoot()
    {
        return $this->_ActiveRecordInstance->find('first', array('conditions' => " ".$this->getScopeCondition()." AND ".$this->getParentColumnName()." IS NULL "));
    }

    /**
     * Returns roots when multiple roots (or virtual root, which is the same)
     */
    public function getRoots()
    {
        return $this->_ActiveRecordInstance->find('all', array('conditions' => " ".$this->getScopeCondition()." AND ".$this->getParentColumnName()." IS NULL ",'order' => $this->getLeftColumnName()));
    }


    /**
     * Returns an array of all parents
     */
    public function &getAncestors()
    {
        $Ancestors =& $this->_ActiveRecordInstance->find('all', array('conditions' => ' '.$this->getScopeCondition().' AND '.
        $this->getLeftColumnName().' < '.$this->_ActiveRecordInstance->get($this->getLeftColumnName()).' AND '.
        $this->getRightColumnName().' > '.$this->_ActiveRecordInstance->get($this->getRightColumnName())
        ,'order' => $this->getLeftColumnName()));
        return $Ancestors;
    }

    /**
     * Returns the array of all parents and self
     */
    public function &getSelfAndAncestors()
    {
        if($result =& $this->getAncestors()){
            array_push($result, $this->_ActiveRecordInstance);
        }else{
            $result = array(&$this->_ActiveRecordInstance);
        }
        return $result;
    }


    /**
     * Returns the array of all children of the parent, except self
     */
    public function getSiblings($search_for_self = false)
    {
        return $this->_ActiveRecordInstance->find('all', array('conditions' => ' (('.$this->getScopeCondition().' AND '.
        $this->getParentColumnName().' = '.$this->_ActiveRecordInstance->get($this->getParentColumnName()).' AND '.
        $this->_ActiveRecordInstance->getPrimaryKey().' <> '.$this->_ActiveRecordInstance->getId().
        ($search_for_self&&!$this->_ActiveRecordInstance->isNewRecord()?') OR ('.$this->_ActiveRecordInstance->getPrimaryKey().' = '.$this->_ActiveRecordInstance->quotedId().'))':'))')
        ,'order' => $this->getLeftColumnName()));
    }

    /**
     * Returns the array of all children of the parent, included self
     */
    public function getSelfAndSiblings()
    {
        $parent_id = $this->_ActiveRecordInstance->get($this->getParentColumnName());
        if(empty($parent_id) || !$result = $this->getSiblings(true)){
            $result = array($this->_ActiveRecordInstance);
        }
        return $result;
    }


    /**
     * Returns the level of this object in the tree
     * root level is 0
     */
    public function getLevel()
    {
        $parent_id = $this->_ActiveRecordInstance->get($this->getParentColumnName());
        if(empty($parent_id)){
            return 0;
        }
        return $this->_ActiveRecordInstance->count(' '.$this->getScopeCondition().' AND '.
        $this->getLeftColumnName().' < '.$this->_ActiveRecordInstance->get($this->getLeftColumnName()).' AND '.
        $this->getRightColumnName().' > '.$this->_ActiveRecordInstance->get($this->getRightColumnName()));
    }


    /**
    * Returns the number of all nested children of this object.
    */
    public function countChildren()
    {
        $children_count = ($this->_ActiveRecordInstance->get($this->getRightColumnName()) - $this->_ActiveRecordInstance->get($this->getLeftColumnName()) - 1)/2;
        return $children_count > 0 ? $children_count : 0;
    }


    /**
     * Returns a set of only this entry's immediate children
     */
    public function getChildren()
    {
        return $this->_ActiveRecordInstance->find('all', array('conditions' => ' '.$this->getScopeCondition().' AND '.
        $this->getParentColumnName().' = '.$this->_ActiveRecordInstance->getId()
        ,'order' => $this->getLeftColumnName()));
    }

    /**
     * Returns a set of all of its children and nested children
     */
    public function getAllChildren()
    {
        $args = func_get_args();
        $excluded_ids = array();
        if(!empty($args)){
            $exclude = count($args) > 1 ? $args : (is_array($args[0]) ? $args[0] : (empty($args[0]) ? false : array($args[0])));
            if(!empty($exclude)){
                $parent_class_name = get_class($this->_ActiveRecordInstance);
                foreach (array_keys($exclude) as $k){
                    $Item =& $exclude[$k];
                    if($Item instanceof $parent_class_name){
                        $ItemToExclude =& $Item;
                    }else{
                        $ItemToExclude =& $this->_ActiveRecordInstance->find($Item);
                    }
                    if($ItemSet = $ItemToExclude->nested_set->getFullSet()){
                        foreach (array_keys($ItemSet) as $l){
                            $excluded_ids[] = $ItemSet[$l]->getId();
                        }
                    }
                }
                $excluded_ids = array_unique(array_diff($excluded_ids,array('')));
            }
        }
        return $this->_ActiveRecordInstance->find('all', array('conditions' => ' '.$this->getScopeCondition().' AND '.
        (empty($excluded_ids) ? '' : ' id NOT IN ('.join(',',$excluded_ids).') AND ').
        $this->getLeftColumnName().' > '.$this->_ActiveRecordInstance->get($this->getLeftColumnName()).' AND '.
        $this->getRightColumnName().' < '.$this->_ActiveRecordInstance->get($this->getRightColumnName())
        ,'order' => $this->getLeftColumnName()));
    }

    /**
     * Returns a set of itself and all of its nested children
     */
    public function getFullSet($exclude = null)
    {
        if($this->_ActiveRecordInstance->isNewRecord() || $this->_ActiveRecordInstance->get($this->getRightColumnName()) - $this->_ActiveRecordInstance->get($this->getLeftColumnName()) == 1 ){
            $result = array($this->_ActiveRecordInstance);
        }else{
        (array)$result = $this->getAllChildren($exclude);
        array_unshift($result, $this->_ActiveRecordInstance);
        }
        return $result;
    }


    /**
     * Move the node to the left of another node
     */
    public function moveToLeftOf($node)
    {
        return $this->moveTo($node, 'left');
    }

    /**
     * Move the node to the left of another node
     */
    public function moveToRightOf($node)
    {
        return $this->moveTo($node, 'right');
    }

    /**
     * Move the node to the child of another node
     */
    public function moveToChildOf($node)
    {
        return $this->moveTo($node, 'child');
    }

    public function moveTo($target, $position)
    {
        if($this->_ActiveRecordInstance->isNewRecord()){
            trigger_error(Ak::t('You cannot move a new node'), E_USER_ERROR);
        }
        $current_left = $this->_ActiveRecordInstance->get($this->getLeftColumnName());
        $current_right = $this->_ActiveRecordInstance->get($this->getRightColumnName());
        // $extent is the width of the tree self and children
        $extent = $current_right - $current_left + 1;

        // load object if node is not an object
        if (is_numeric($target)){
            $target =& $this->_ActiveRecordInstance->find($target);
        }
        if(!$target){
            trigger_error(Ak::t('Invalid target'), E_USER_NOTICE);
            return false;
        }

        $target_left = $target->get($this->getLeftColumnName());
        $target_right = $target->get($this->getRightColumnName());


        // detect impossible move
        if ((($current_left <= $target_left) && ($target_left <= $current_right)) || (($current_left <= $target_right) && ($target_right <= $current_right))){
            trigger_error(Ak::t('Impossible move, target node cannot be inside moved tree.'), E_USER_ERROR);
        }

        // compute new left/right for self
        if ($position == 'child'){
            if ($target_left < $current_left){
                $new_left  = $target_left + 1;
                $new_right = $target_left + $extent;
            }else{
                $new_left  = $target_left - $extent + 1;
                $new_right = $target_left;
            }
        }elseif($position == 'left'){
            if ($target_left < $current_left){
                $new_left  = $target_left;
                $new_right = $target_left + $extent - 1;
            }else{
                $new_left  = $target_left - $extent;
                $new_right = $target_left - 1;
            }
        }elseif($position == 'right'){
            if ($target_right < $current_right){
                $new_left  = $target_right + 1;
                $new_right = $target_right + $extent;
            }else{
                $new_left  = $target_right - $extent + 1;
                $new_right = $target_right;
            }
        }else{
            trigger_error(Ak::t("Position should be either left or right ('%position' received).",array('%position'=>$position)), E_USER_ERROR);
        }

        // boundaries of update action
        $left_boundary = min($current_left, $new_left);
        $right_boundary = max($current_right, $new_right);

        // Shift value to move self to new $position
        $shift = $new_left - $current_left;

        // Shift value to move nodes inside boundaries but not under self_and_children
        $updown = ($shift > 0) ? -$extent : $extent;

        // change null to NULL for new parent
        if($position == 'child'){
            $new_parent = $target->getId();
        }else{
            $target_parent = $target->get($this->getParentColumnName());
            $new_parent = empty($target_parent) ? 'NULL' : $target_parent;
        }

        $this->_ActiveRecordInstance->updateAll(

        $this->getLeftColumnName().' = CASE '.
        'WHEN '.$this->getLeftColumnName().' BETWEEN '.$current_left.' AND '.$current_right.' '.
        'THEN '.$this->getLeftColumnName().' + '.$shift.' '.
        'WHEN '.$this->getLeftColumnName().' BETWEEN '.$left_boundary.' AND '.$right_boundary.' '.
        'THEN '.$this->getLeftColumnName().' + '.$updown.' '.
        'ELSE '.$this->getLeftColumnName().' END, '.

        $this->getRightColumnName().' = CASE '.
        'WHEN '.$this->getRightColumnName().' BETWEEN '.$current_left.' AND '.$current_right.' '.
        'THEN '.$this->getRightColumnName().' + '.$shift.' '.
        'WHEN '.$this->getRightColumnName().' BETWEEN '.$left_boundary.' AND '.$right_boundary.' '.
        'THEN '.$this->getRightColumnName().' + '.$updown.' '.
        'ELSE '.$this->getRightColumnName().' END, '.

        $this->getParentColumnName().' = CASE '.
        'WHEN '.$this->_ActiveRecordInstance->getPrimaryKey().' = '.$this->_ActiveRecordInstance->getId().' '.
        'THEN '.$new_parent.' '.
        'ELSE '.$this->getParentColumnName().' END',

        $this->getScopeCondition() );
        $this->_ActiveRecordInstance->reload();

        return true;
    }

}



/*
# Copyright (c) 2007, 2008 Pythonic Pty Ltd
# http://www.pythonic.com.au/

class Integer
# Returns modular multiplicative inverse.
# Examples:
#   2.inverse(7) # => 4
#   4.inverse(7) # => 2
def inverse(m)
u, v = m, self
x, y = 0, 1
while v != 0
q, r = u.divmod(v)
x, y = y, x - q * y
u, v = v, r
end
if u.abs == 1
x < 0 ? x + m : x
end
end
end

module ActiveRecord::Acts
module NestedInterval
def self.included(base)
base.extend(ClassMethods)
end

# This act implements a nested-interval tree. You can find all descendants
# or all ancestors with just one select query. You can insert and delete
# records without a full table update.

module ClassMethods
# The +options+ hash can include:
# * <tt>:foreign_key</tt> -- the self-reference foreign key column name (default :parent_id).
# * <tt>:scope_columns</tt> -- an array of columns to scope independent trees.
# * <tt>:lft_index</tt> -- whether to use functional index for lft (default false).
def acts_as_nested_interval(options = {})
cattr_accessor :nested_interval_foreign_key
cattr_accessor :nested_interval_scope_columns
cattr_accessor :nested_interval_lft_index
self.nested_interval_foreign_key = options[:foreign_key] || :parent_id
self.nested_interval_scope_columns = Array(options[:scope_columns])
self.nested_interval_lft_index = options[:lft_index]
belongs_to :parent, :class_name => name, :foreign_key => nested_interval_foreign_key
has_many :children, :class_name => name, :foreign_key => nested_interval_foreign_key, :dependent => :destroy
named_scope :roots, :conditions => {nested_interval_foreign_key => nil}
if columns_hash["rgt"]
named_scope :preorder, :order => %(rgt DESC, lftp ASC)
elsif columns_hash["rgtp"] && columns_hash["rgtq"]
named_scope :preorder, :order => %(1.0 * rgtp / rgtq DESC, lftp ASC)
else
named_scope :preorder, :order => %(nested_interval_rgt(lftp, lftq) DESC, lftp ASC)
end
class_eval do
include ActiveRecord::Acts::NestedInterval::InstanceMethods
alias_method_chain :create, :nested_interval
alias_method_chain :destroy, :nested_interval
alias_method_chain :update, :nested_interval
if columns_hash["lft"]
def descendants
quoted_table_name = self.class.quoted_table_name
nested_interval_scope.scoped :conditions => %(#{lftp} < #{quoted_table_name}.lftp AND #{quoted_table_name}.lft BETWEEN #{1.0 * lftp / lftq} AND #{1.0 * rgtp / rgtq})
end
elsif nested_interval_lft_index
def descendants
quoted_table_name = self.class.quoted_table_name
nested_interval_scope.scoped :conditions => %(#{lftp} < #{quoted_table_name}.lftp AND 1.0 * #{quoted_table_name}.lftp / #{quoted_table_name}.lftq BETWEEN #{1.0 * lftp / lftq} AND #{1.0 * rgtp / rgtq})
end
elsif connection.adapter_name == "MySQL"
def descendants
quoted_table_name = self.class.quoted_table_name
nested_interval_scope.scoped :conditions => %((#{quoted_table_name}.lftp != #{rgtp} OR #{quoted_table_name}.lftq != #{rgtq}) AND #{quoted_table_name}.lftp BETWEEN 1 + #{quoted_table_name}.lftq * #{lftp} DIV #{lftq} AND #{quoted_table_name}.lftq * #{rgtp} DIV #{rgtq})
end
else
def descendants
quoted_table_name = self.class.quoted_table_name
nested_interval_scope.scoped :conditions => %((#{quoted_table_name}.lftp != #{rgtp} OR #{quoted_table_name}.lftq != #{rgtq}) AND #{quoted_table_name}.lftp BETWEEN 1 + #{quoted_table_name}.lftq * CAST(#{lftp} AS BIGINT) / #{lftq} AND #{quoted_table_name}.lftq * CAST(#{rgtp} AS BIGINT) / #{rgtq})
end
end
end
end
end

module InstanceMethods
def set_nested_interval(lftp, lftq)
self.lftp, self.lftq = lftp, lftq
self.rgtp = rgtp if has_attribute?(:rgtp)
self.rgtq = rgtq if has_attribute?(:rgtq)
self.lft = lft if has_attribute?(:lft)
self.rgt = rgt if has_attribute?(:rgt)
end

# Creates record.
def create_with_nested_interval
if read_attribute(nested_interval_foreign_key).nil?
set_nested_interval 0, 1
else
set_nested_interval *parent.lock!.next_child_lft
end
create_without_nested_interval
end

# Destroys record.
def destroy_with_nested_interval
lock! rescue nil
destroy_without_nested_interval
end

def nested_interval_scope
conditions = {}
nested_interval_scope_columns.each do |column_name|
conditions[column_name] = send(column_name)
end
self.class.scoped(:conditions => conditions)
end

# Updates record, updating descendants if parent association updated,
# in which case caller should first acquire table lock.
def update_with_nested_interval
if read_attribute(nested_interval_foreign_key).nil?
set_nested_interval 0, 1
elsif !parent.updated?
db_self = self.class.find(id, :lock => true)
write_attribute(nested_interval_foreign_key, db_self.read_attribute(nested_interval_foreign_key))
set_nested_interval db_self.lftp, db_self.lftq
else
# No locking in this case -- caller should have acquired table lock.
db_self = self.class.find(id)
db_parent = self.class.find(read_attribute(nested_interval_foreign_key))
if db_parent.lftp == db_self.lftp && db_parent.lftq == db_self.lftq \
|| db_parent.lftp > db_parent.lftq * db_self.lftp / db_self.lftq \
&& db_parent.lftp <= db_parent.lftq * db_self.rgtp / db_self.rgtq \
&& (db_parent.lftp != db_self.rgtp || db_parent.lftq != db_self.rgtq)
errors.add :parent_id, "is descendant"
raise ActiveRecord::RecordInvalid, self
end
set_nested_interval *parent.next_child_lft
mysql_tmp = "@" if connection.adapter_name == "MySQL"
cpp = db_self.lftq * rgtp - db_self.rgtq * lftp
cpq = db_self.rgtp * lftp - db_self.lftp * rgtp
cqp = db_self.lftq * rgtq - db_self.rgtq * lftq
cqq = db_self.rgtp * lftq - db_self.lftp * rgtq
db_descendants = db_self.descendants
if has_attribute?(:rgtp) && has_attribute?(:rgtq)
db_descendants.update_all %(
rgtp = #{cpp} * rgtp + #{cpq} * rgtq,
rgtq = #{cqp} * #{mysql_tmp}rgtp + #{cqq} * rgtq
), mysql_tmp && %(@rgtp := rgtp)
db_descendants.update_all %(rgt = 1.0 * rgtp / rgtq) if has_attribute?(:rgt)
end
db_descendants.update_all %(
lftp = #{cpp} * lftp + #{cpq} * lftq,
lftq = #{cqp} * #{mysql_tmp}lftp + #{cqq} * lftq
), mysql_tmp && %(@lftp := lftp)
db_descendants.update_all %(lft = 1.0 * lftp / lftq) if has_attribute?(:lft)
end
update_without_nested_interval
end

def ancestors
sqls = [%(NULL)]
p, q = lftp, lftq
while p != 0
x = p.inverse(q)
p, q = (x * p - 1) / q, x
sqls << %(lftq = #{q} AND lftp = #{p})
end
nested_interval_scope.scoped :conditions => sqls * %( OR )
end

# Returns depth by counting ancestors up to 0 / 1.
def depth
n = 0
p, q = lftp, lftq
while p != 0
x = p.inverse(q)
p, q = (x * p - 1) / q, x
n += 1
end
n
end

def lft
1.0 * lftp / lftq
end

def rgt
1.0 * rgtp / rgtq
end

# Returns numerator of right end of interval.
def rgtp
case lftp
when 0
1
when 1
1
else
lftq.inverse(lftp)
end
end

# Returns denominator of right end of interval.
def rgtq
case lftp
when 0
1
when 1
lftq - 1
else
(lftq.inverse(lftp) * lftq - 1) / lftp
end
end

# Returns left end of interval for next child.
def next_child_lft
if child = children.find(:first, :order => %(lftq DESC))
return lftp + child.lftp, lftq + child.lftq
else
return lftp + rgtp, lftq + rgtq
end
end
end
end
end

ActiveRecord::Base.send :include, ActiveRecord::Acts::NestedInterval


*/

?>