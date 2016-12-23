<?php
if (!extension_loaded("pthreads")) {

    class Pool
    {
        /**
         * Pool constructor.
         * @param $size
         * @param $class
         * @param array $ctor
         */
        public function __construct($size, $class = \Worker::class, $ctor = array())
        {
            $this->size  = $size;
            $this->clazz = $class;
            $this->ctor  = $ctor;
        }

        public function submit(Threaded $collectable)
        {
            if ($this->last > $this->size) {
                $this->last = 0;
            }

            if (!isset($this->workers[$this->last])) {
                if (is_object($collectable) && $collectable instanceof $this->clazz) {
                    $Instance = $collectable;
                } elseif (version_compare(phpversion(), '5.6.0', '>=')) {
                    $Instance = new $this->clazz(...$this->ctor);
                } else {
                    $Reflect  = new ReflectionClass($this->clazz);
                    $Instance = $Reflect->newInstanceArgs($this->ctor);
                }

                $this->workers[$this->last] = $Instance;
                $this->workers[$this->last]->start();
            }

            $this->workers[$this->last++]->stack($collectable);
        }

        public function submitTo($worker, Threaded $collectable)
        {
            if (isset($this->workers[$worker])) {
                $this->workers[$worker]->stack($collectable);
            }
        }

        public function collect(Closure $collector = null)
        {
            $total = 0;
            foreach ($this->workers as $worker) {
                $total += $worker->collect($collector);
            }
            return $total;
        }

        public function resize($size)
        {
            if ($size < $this->size) {
                while ($this->size > $size) {
                    if (isset($this->workers[$this->size - 1])) {
                        $this->workers[$this->size - 1]->shutdown();
                    }
                    unset($this->workers[$this->size - 1]);
                    $this->size--;
                }
            }
        }

        public function shutdown()
        {
            unset($this->workers);
        }

        protected $workers;
        protected $size;
        protected $last;
        protected $clazz;
        protected $ctor;
    }
}


